<?php

namespace App\Services;

use App\Models\FundTransaction;
use App\Models\FundTransactionDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FundTransactionService
{
    /**
     * Generate a unique voucher number for the current month.
     *
     * Format: DV-YYYYMM-0001
     */
    public function generateVoucherNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = sprintf('DV-%s%s-', $year, $month);

        $lastVoucher = FundTransaction::withoutTrashed()
            ->where('voucher_number', 'like', $prefix . '%')
            ->orderBy('voucher_number', 'desc')
            ->first();

        $nextNumber = $lastVoucher
            ? (int) substr($lastVoucher->voucher_number, -4) + 1
            : 1;

        return $prefix . sprintf('%04d', $nextNumber);
    }

    /**
     * Create a new fund transaction.
     *
     * @param array $data Validated data from FormRequest
     * @return FundTransaction
     */
    public function create(array $data): FundTransaction
    {
        return DB::transaction(function () use ($data) {
            $data['voucher_number'] = $this->generateVoucherNumber();

            $voucher = FundTransaction::create($data);

            Log::info('fund_transaction_created', [
                'id' => $voucher->id,
                'voucher_number' => $voucher->voucher_number,
                'created_by' => $data['created_by'] ?? null,
            ]);

            return $voucher;
        });
    }

    /**
     * Update an existing fund transaction.
     *
     * @param FundTransaction $voucher
     * @param array $data Validated data from FormRequest
     * @return FundTransaction
     */
    public function update(FundTransaction $voucher, array $data): FundTransaction
    {
        Log::debug('FundTransaction update attempt', [
            'id' => $voucher->id,
            'transaction_status' => $data['transaction_status'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ]);

        $voucher->update($data);
        $voucher->refresh();

        Log::debug('FundTransaction update result', [
            'id' => $voucher->id,
            'transaction_status_after' => $voucher->transaction_status,
        ]);

        return $voucher;
    }

    /**
     * Update only the status and remarks of a fund transaction.
     *
     * @param FundTransaction $voucher
     * @param array $data Validated data (transaction_status, remarks)
     * @return FundTransaction
     */
    public function updateStatus(FundTransaction $voucher, array $data): FundTransaction
    {
        Log::debug('Updating status for voucher ' . $voucher->id, $data);

        $voucher->update($data);
        $voucher->refresh();

        Log::debug('Status updated for voucher ' . $voucher->id, [
            'transaction_status' => $voucher->transaction_status,
            'remarks' => $voucher->remarks,
        ]);

        return $voucher;
    }

    /**
     * Soft-delete a fund transaction.
     */
    public function delete(FundTransaction $voucher): bool
    {
        $voucher->delete();
        return true;
    }

    /**
     * Upload a document for a fund transaction.
     *
     * @param FundTransaction $voucher
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $documentType
     * @return FundTransactionDocument
     */
    public function uploadDocument(FundTransaction $voucher, $file, string $documentType): FundTransactionDocument
    {
        $dir = "documents/fund-transactions/{$voucher->id}";
        $documentPath = storage_path("app/{$dir}");

        if (!file_exists($documentPath)) {
            mkdir($documentPath, 0755, true);
        }

        $filename = $documentType . '_' . time() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs($dir, $filename, 'local');

        // Replace existing document of same type
        FundTransactionDocument::where('fund_transaction_id', $voucher->id)
            ->where('document_type', $documentType)
            ->delete();

        $document = FundTransactionDocument::create([
            'fund_transaction_id' => $voucher->id,
            'document_type' => $documentType,
            'filename' => $filename,
            'path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        $document->generateQRCode();

        return $document;
    }

    /**
     * Delete a document from a fund transaction.
     *
     * @param FundTransaction $voucher
     * @param string $docType
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteDocument(FundTransaction $voucher, string $docType): bool
    {
        $document = FundTransactionDocument::where('fund_transaction_id', $voucher->id)
            ->where('document_type', $docType)
            ->firstOrFail();

        $fullPath = storage_path('app/' . $document->path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $document->delete();

        return true;
    }

    /**
     * Verify a document via QR code data.
     *
     * @param FundTransaction $voucher
     * @param string $qrCode Base64-encoded JSON
     * @return FundTransactionDocument
     * @throws \InvalidArgumentException
     */
    public function verifyDocumentQR(FundTransaction $voucher, string $qrCode): FundTransactionDocument
    {
        $qrData = json_decode(base64_decode($qrCode), true);

        if (!$qrData || !isset($qrData['document_id'])) {
            throw new \InvalidArgumentException('Invalid QR code');
        }

        $document = FundTransactionDocument::findOrFail($qrData['document_id']);

        if ($document->fund_transaction_id != $voucher->id) {
            throw new \InvalidArgumentException('Document does not belong to this fund transaction');
        }

        $document->markAsVerified();

        return $document;
    }
}
