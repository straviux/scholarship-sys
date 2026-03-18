<?php

namespace App\Services;

use App\Models\FundTransaction;
use App\Models\FundTransactionDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FundTransactionFileService
{
    public function __construct(
        private FileUploadService $fileUploadService,
    ) {}

    /**
     * Store a fund transaction document with file processing
     * 
     * @param FundTransaction $transaction
     * @param UploadedFile $file
     * @param string $documentType (obr|dv_payroll|los|cheque)
     * @return FundTransactionDocument
     * @throws \Exception
     */
    public function storeDocument(FundTransaction $transaction, UploadedFile $file, string $documentType): FundTransactionDocument
    {
        // Process the file (compress if image/PDF)
        $result = $this->fileUploadService->processUpload($file);

        if (!$result->isSuccess()) {
            throw new \Exception($result->getErrorMessage() ?? 'File processing failed');
        }

        // Generate filename with document type and timestamp
        $timestamp = date('YmdHis');
        $fileName = "{$documentType}_{$timestamp}.{$result->extension}";

        // Store file in fund transactions directory
        $dir = "documents/fund-transactions/{$transaction->id}";
        $filePath = "{$dir}/{$fileName}";

        Storage::disk('public')->put($filePath, $result->content);

        // Create or update database record
        $document = FundTransactionDocument::updateOrCreate(
            [
                'fund_transaction_id' => $transaction->id,
                'document_type' => $documentType,
            ],
            [
                'filename' => $file->getClientOriginalName(),
                'path' => Storage::url($filePath),
                'file_size' => $result->compressedSize,
                'original_file_size' => $result->originalSize,
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => Auth::id(),
                'verified' => false,
            ]
        );

        // Log upload with compression metrics
        Log::info('fund_transaction_file_uploaded', [
            'transaction_id' => $transaction->id,
            'document_type' => $documentType,
            'filename' => $fileName,
            'file_size' => $result->compressedSize,
            'original_size' => $result->originalSize,
            'compression_ratio' => $result->getCompressionRatio(),
            'uploaded_by' => Auth::id(),
        ]);

        return $document;
    }
}
