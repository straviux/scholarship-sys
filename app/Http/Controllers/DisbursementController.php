<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Models\Disbursement;
use App\Models\DisbursementAttachment;
use App\Models\FundTransaction;
use App\Models\FundTransactionDocument;
use App\Models\ScholarshipProfile;
use App\Services\ActivityLogService;
use App\Services\FundTransactionService;
use App\Services\FundTransactionFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * DisbursementController - adapter layer
 *
 * Fund transactions are the single source of truth for all disbursements.
 * Legacy disbursements remain readable (read-only/historical).
 *
 * ID conventions:
 *   "FT-{id}"  => fund_transactions.id
 *   integer    => legacy disbursements.disbursement_id
 *
 * Attachment ID conventions:
 *   "FTD-{id}" => fund_transaction_documents.id
 *   integer    => legacy disbursement_attachments.attachment_id
 */
class DisbursementController extends Controller
{
    public function __construct(
        private FundTransactionService $ftService,
        private FundTransactionFileService $ftFileService,
    ) {}

    // READ

    public function index(Request $request, $profileId)
    {
        $profileIdInt = (int) $profileId;

        $fundTransactions = FundTransaction::with(['documents', 'cheques'])
            ->where(function ($query) use ($profileId, $profileIdInt) {
                // Cover plain array (int), plain array (string), object with int, object with string
                $query->whereJsonContains('scholar_ids', $profileIdInt)
                    ->orWhereJsonContains('scholar_ids', (string) $profileId)
                    ->orWhereJsonContains('scholar_ids', ['profile_id' => $profileIdInt])
                    ->orWhereJsonContains('scholar_ids', ['profile_id' => (string) $profileId]);
            })
            ->orderBy('date_obligated', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $normalized = $fundTransactions->map(fn($ft) => $this->normalizeFundTransaction($ft, $profileId));

        $legacyDisbursements = Disbursement::with([
            'cheques',
            'creator',
            'attachments',
            'profile.scholarshipGrant.course',
            'profile.scholarshipGrant.school',
        ])
            ->where('profile_id', $profileId)
            ->orderBy('date_obligated', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $legacyNormalized = $legacyDisbursements->map(fn($d) => $this->normalizeLegacyDisbursement($d));

        $combined = $normalized->concat($legacyNormalized)
            ->sortByDesc(fn($item) => $item['date_obligated'] ?? '')
            ->values();

        return response()->json($combined);
    }

    // CREATE / UPDATE / DELETE

    public function store(Request $request)
    {
        $exemptStatuses = ['LOA', 'IRREGULAR', 'TRANSFERRED'];
        $isExempt = in_array($request->obr_status, $exemptStatuses);

        $validated = $request->validate([
            'profile_id'        => 'required',
            'disbursement_type' => $isExempt ? 'nullable|in:regular,reimbursement,financial_assistance' : 'required|in:regular,reimbursement,financial_assistance',
            'payee'             => $isExempt ? 'nullable|string|max:255' : 'required|string|max:255',
            'obr_no'            => 'nullable|string|max:255',
            'obr_status'        => 'nullable|string|max:100',
            'date_obligated'    => 'nullable|date',
            'year_level'        => 'nullable|string|max:255',
            'semester'          => 'nullable|string|max:255',
            'academic_year'     => 'nullable|string|max:255',
            'amount'            => $isExempt ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'remarks'           => 'nullable|string',
        ]);

        $profile = ScholarshipProfile::where('profile_id', $validated['profile_id'])->first();

        $ftData = [
            'disbursement_type'  => 'disbursements',
            'obr_type'           => $this->toObrType($validated['disbursement_type'] ?? null),
            'payee_name'         => $validated['payee'] ?? 'Scholar',
            'payee_type'         => 'scholar',
            'obr_no'             => $validated['obr_no'] ?? null,
            'transaction_status' => $validated['obr_status'] ?? 'ON PROCESS',
            'date_obligated'     => $validated['date_obligated'] ?? null,
            'year_level'         => $validated['year_level'] ?? null,
            'semester'           => $validated['semester'] ?? null,
            'academic_year'      => $validated['academic_year'] ?? null,
            'amount'             => $validated['amount'] ?? 0,
            'remarks'            => $validated['remarks'] ?? null,
            'scholar_ids'        => [[
                'profile_id' => $validated['profile_id'],
                'name'       => $profile?->full_name ?? 'Unknown',
                'amount'     => $validated['amount'] ?? 0,
            ]],
            'created_by' => Auth::id(),
        ];

        $ft = $this->ftService->create($ftData);

        ActivityLogService::logRecordCreated(
            profileId: $validated['profile_id'],
            recordData: [
                'disbursement_type' => $validated['disbursement_type'] ?? 'N/A',
                'payee'             => $validated['payee'] ?? 'N/A',
                'amount'            => $validated['amount'] ?? 0,
                'obr_status'        => $validated['obr_status'] ?? 'N/A',
            ],
            remarks: 'Created disbursement: ' . ($validated['payee'] ?? 'N/A') . ' - ' . ($validated['amount'] ?? 0),
        );

        return response()->json([
            'success'      => true,
            'message'      => 'Disbursement created successfully',
            'disbursement' => $this->normalizeFundTransaction($ft->load(['documents', 'cheques']), $validated['profile_id']),
        ]);
    }

    public function update(Request $request, $id)
    {
        if (str_starts_with((string) $id, 'FT-')) {
            return $this->updateFundTransaction($request, (int) substr($id, 3));
        }
        return $this->updateLegacyDisbursement($request, $id);
    }

    public function destroy($id)
    {
        if (str_starts_with((string) $id, 'FT-')) {
            return $this->destroyFundTransaction((int) substr($id, 3));
        }
        return $this->destroyLegacyDisbursement($id);
    }

    // CHEQUES

    public function addCheque(Request $request, $disbursementId)
    {
        $validated = $request->validate([
            'cheque_no'     => 'required|string|max:255',
            'date_released' => 'nullable|date',
            'remarks'       => 'nullable|string',
        ]);
        $validated['processed_by'] = Auth::id();

        if (str_starts_with((string) $disbursementId, 'FT-')) {
            $ftId = (int) substr($disbursementId, 3);
            $ft = FundTransaction::findOrFail($ftId);
            $validated['fund_transaction_id'] = $ftId;
            $cheque = Cheque::create($validated);
            ActivityLogService::logRecordCreated(
                profileId: $this->getFirstProfileId($ft),
                recordData: ['cheque_no' => $validated['cheque_no']],
                remarks: 'Added cheque: ' . $validated['cheque_no'],
            );
        } else {
            $disbursement = Disbursement::findOrFail($disbursementId);
            $validated['disbursement_id'] = $disbursementId;
            $cheque = Cheque::create($validated);
            ActivityLogService::logRecordCreated(
                profileId: $disbursement->profile_id,
                recordData: ['cheque_no' => $validated['cheque_no']],
                remarks: 'Added cheque: ' . $validated['cheque_no'],
            );
        }

        return response()->json(['success' => true, 'message' => 'Cheque added successfully', 'cheque' => $cheque->load('processor')]);
    }

    public function updateCheque(Request $request, $chequeId)
    {
        $validated = $request->validate([
            'cheque_no'     => 'required|string|max:255',
            'date_released' => 'nullable|date',
            'remarks'       => 'nullable|string',
        ]);

        $cheque = Cheque::findOrFail($chequeId);
        $oldData = $cheque->getAttributes();
        $cheque->update($validated);

        $profileId = $cheque->disbursement?->profile_id
            ?? $this->getFirstProfileId($cheque->fundTransaction);

        ActivityLogService::logRecordUpdated(
            profileId: $profileId,
            oldData: $oldData,
            newData: $cheque->fresh()->getAttributes(),
            remarks: 'Updated cheque: ' . $validated['cheque_no'],
        );

        return response()->json(['success' => true, 'message' => 'Cheque updated successfully', 'cheque' => $cheque->load('processor')]);
    }

    public function destroyCheque($chequeId)
    {
        $cheque = Cheque::findOrFail($chequeId);
        $chequeData = $cheque->getAttributes();
        $profileId = $cheque->disbursement?->profile_id
            ?? $this->getFirstProfileId($cheque->fundTransaction);
        $cheque->delete();

        ActivityLogService::logRecordDeleted(
            profileId: $profileId,
            recordData: $chequeData,
            remarks: 'Deleted cheque: ' . ($chequeData['cheque_no'] ?? ''),
        );

        return response()->json(['success' => true, 'message' => 'Cheque deleted successfully']);
    }

    // ATTACHMENTS

    public function uploadAttachment(Request $request, $disbursementId)
    {
        $validated = $request->validate([
            'attachment_type' => 'required|in:voucher,cheque,receipt,obr,dv_payroll,los',
            'file'            => 'required|file|mimes:pdf,jpg,jpeg,png|max:25600',
        ]);

        if (str_starts_with((string) $disbursementId, 'FT-')) {
            $ftId = (int) substr($disbursementId, 3);
            $ft = FundTransaction::with(['documents', 'cheques'])->findOrFail($ftId);
            $doc = $this->ftFileService->storeDocument($ft, $request->file('file'), $validated['attachment_type']);

            ActivityLogService::logAttachmentUploaded(
                profileId: $this->getFirstProfileId($ft),
                attachmentName: $validated['attachment_type'],
                fileName: $doc->filename,
                remarks: 'Uploaded ' . $validated['attachment_type'] . ': ' . $doc->filename,
            );

            $ft->refresh()->load(['documents', 'cheques']);
            return response()->json([
                'success'     => true,
                'message'     => 'Attachment uploaded successfully',
                'attachment'  => $this->normalizeDocument($doc, $ftId),
                'attachments' => $this->normalizeDocuments($ft->documents, $ftId),
            ]);
        }

        $disbursement = Disbursement::with(['profile', 'attachments'])->findOrFail($disbursementId);
        $profile = $disbursement->profile;
        $uniqueId = $profile->unique_id;
        $scholarName = preg_replace('/[^A-Za-z0-9_]/', '_', $profile->first_name . '_' . $profile->last_name);
        $attachmentType = $validated['attachment_type'];
        $file = $request->file('file');
        $timestamp = now()->format('Ymd_His');
        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = "disbursement_{$scholarName}_{$attachmentType}_{$timestamp}.{$extension}";
        $filePath = $file->storeAs("attachments/{$uniqueId}", $fileName, 'public');

        $attachment = DisbursementAttachment::create([
            'disbursement_id' => $disbursementId,
            'attachment_type' => $attachmentType,
            'file_name'       => $fileName,
            'file_path'       => $filePath,
            'file_type'       => $file->getMimeType(),
            'file_size'       => $file->getSize(),
        ]);

        ActivityLogService::logAttachmentUploaded(
            profileId: $disbursement->profile_id,
            attachmentName: $attachmentType,
            fileName: $fileName,
            remarks: 'Uploaded ' . $attachmentType . ': ' . $fileName,
        );

        $disbursement->refresh()->load('attachments');
        return response()->json([
            'success'     => true,
            'message'     => 'Attachment uploaded successfully',
            'attachment'  => $attachment,
            'attachments' => $disbursement->attachments,
        ]);
    }

    public function deleteAttachment($attachmentId)
    {
        if (str_starts_with((string) $attachmentId, 'FTD-')) {
            $docId = (int) substr($attachmentId, 4);
            $doc = FundTransactionDocument::findOrFail($docId);
            $ft = $doc->fundTransaction;

            $storagePath = ltrim(parse_url($doc->path, PHP_URL_PATH), '/');
            if (str_starts_with($storagePath, 'storage/')) {
                $storagePath = substr($storagePath, 8);
            }
            if (Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->delete($storagePath);
            }

            ActivityLogService::logAttachmentDeleted(
                profileId: $this->getFirstProfileId($ft),
                attachmentName: $doc->document_type,
                fileName: $doc->filename,
                remarks: 'Deleted ' . $doc->document_type . ': ' . $doc->filename,
            );

            $doc->delete();
            $ft->refresh()->load('documents');

            return response()->json([
                'success'     => true,
                'message'     => 'Attachment deleted successfully',
                'attachments' => $this->normalizeDocuments($ft->documents, $ft->id),
            ]);
        }

        $attachment = DisbursementAttachment::findOrFail($attachmentId);
        $disbursement = $attachment->disbursement;
        $attachmentData = $attachment->getAttributes();

        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }
        $attachment->delete();

        ActivityLogService::logAttachmentDeleted(
            profileId: $disbursement->profile_id,
            attachmentName: $attachmentData['attachment_type'],
            fileName: $attachmentData['file_name'],
            remarks: 'Deleted ' . $attachmentData['attachment_type'] . ': ' . $attachmentData['file_name'],
        );

        $disbursement->refresh()->load('attachments');
        return response()->json([
            'success'     => true,
            'message'     => 'Attachment deleted successfully',
            'attachments' => $disbursement->attachments,
        ]);
    }

    public function downloadAttachment($attachmentId)
    {
        if (str_starts_with((string) $attachmentId, 'FTD-')) {
            $doc = FundTransactionDocument::findOrFail((int) substr($attachmentId, 4));
            $path = ltrim(parse_url($doc->path, PHP_URL_PATH), '/');
            if (str_starts_with($path, 'storage/')) {
                $path = substr($path, 8);
            }
            if (!Storage::disk('public')->exists($path)) {
                return response()->json(['error' => 'File not found'], 404);
            }
            $content = Storage::disk('public')->get($path);
            $tempPath = storage_path('app/temp/' . $doc->filename);
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            file_put_contents($tempPath, $content);
            return response()->download($tempPath, $doc->filename)->deleteFileAfterSend(true);
        }

        $attachment = DisbursementAttachment::findOrFail($attachmentId);
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }
        $content = Storage::disk('public')->get($attachment->file_path);
        $tempPath = storage_path('app/temp/' . $attachment->file_name);
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        file_put_contents($tempPath, $content);
        return response()->download($tempPath, $attachment->file_name)->deleteFileAfterSend(true);
    }

    public function viewAttachment($attachmentId)
    {
        if (str_starts_with((string) $attachmentId, 'FTD-')) {
            $doc = FundTransactionDocument::findOrFail((int) substr($attachmentId, 4));
            $path = ltrim(parse_url($doc->path, PHP_URL_PATH), '/');
            if (str_starts_with($path, 'storage/')) {
                $path = substr($path, 8);
            }
            if (!Storage::disk('public')->exists($path)) {
                return response()->json(['error' => 'File not found'], 404);
            }
            $content = Storage::disk('public')->get($path);
            return response($content)
                ->header('Content-Type', $doc->mime_type)
                ->header('Content-Disposition', 'inline; filename="' . $doc->filename . '"');
        }

        $attachment = DisbursementAttachment::findOrFail($attachmentId);
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }
        $content = Storage::disk('public')->get($attachment->file_path);
        return response($content)
            ->header('Content-Type', $attachment->file_type)
            ->header('Content-Disposition', 'inline; filename="' . $attachment->file_name . '"');
    }

    // QR CODE

    public function generateQrCode($disbursementId)
    {
        if (str_starts_with((string) $disbursementId, 'FT-')) {
            $ft = FundTransaction::findOrFail((int) substr($disbursementId, 3));
            $ft->generateUploadToken();
            return response()->json([
                'qr_code'    => $ft->getUploadQrCode(250),
                'url'        => $ft->getMobileUploadUrl(),
                'expires_at' => $ft->upload_token_expires_at?->toIso8601String(),
            ]);
        }

        $disbursement = Disbursement::findOrFail($disbursementId);
        $disbursement->generateUploadToken();
        return response()->json([
            'qr_code'    => $disbursement->getUploadQrCode(250),
            'url'        => $disbursement->getMobileUploadUrl(),
            'expires_at' => $disbursement->upload_token_expires_at?->toIso8601String(),
        ]);
    }

    // PRIVATE HELPERS

    private function updateFundTransaction(Request $request, int $ftId)
    {
        $exemptStatuses = ['LOA', 'IRREGULAR', 'TRANSFERRED'];
        $isExempt = in_array($request->obr_status, $exemptStatuses);

        $validated = $request->validate([
            'disbursement_type' => $isExempt ? 'nullable|in:regular,reimbursement,financial_assistance' : 'required|in:regular,reimbursement,financial_assistance',
            'payee'             => $isExempt ? 'nullable|string|max:255' : 'required|string|max:255',
            'obr_no'            => 'nullable|string|max:255',
            'obr_status'        => 'nullable|string|max:100',
            'date_obligated'    => 'nullable|date',
            'year_level'        => 'nullable|string|max:255',
            'semester'          => 'nullable|string|max:255',
            'academic_year'     => 'nullable|string|max:255',
            'amount'            => $isExempt ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'remarks'           => 'nullable|string',
            'profile_id'        => 'nullable',
        ]);

        $ft = FundTransaction::with(['documents', 'cheques'])->findOrFail($ftId);
        $oldData = $ft->getAttributes();

        // Keep scholar_ids amount in sync
        if (isset($validated['amount']) && isset($validated['profile_id']) && $ft->scholar_ids) {
            $scholarIds = $ft->scholar_ids;
            foreach ($scholarIds as &$scholar) {
                if (is_array($scholar) && ($scholar['profile_id'] ?? null) == $validated['profile_id']) {
                    $scholar['amount'] = $validated['amount'];
                }
            }
            unset($scholar);
            $ft->scholar_ids = $scholarIds;
        }

        $updateData = [
            'obr_type'           => $this->toObrType($validated['disbursement_type'] ?? null),
            'payee_name'         => $validated['payee'] ?? $ft->payee_name,
            'obr_no'             => $validated['obr_no'] ?? $ft->obr_no,
            'transaction_status' => $validated['obr_status'] ?? $ft->transaction_status,
            'date_obligated'     => $validated['date_obligated'] ?? $ft->date_obligated,
            'year_level'         => $validated['year_level'] ?? $ft->year_level,
            'semester'           => $validated['semester'] ?? $ft->semester,
            'academic_year'      => $validated['academic_year'] ?? $ft->academic_year,
            'amount'             => $validated['amount'] ?? $ft->amount,
            'remarks'            => $validated['remarks'] ?? $ft->remarks,
            'updated_by'         => Auth::id(),
        ];

        $this->ftService->update($ft, $updateData);
        $ft->save();
        $ft->refresh()->load(['documents', 'cheques']);

        ActivityLogService::logRecordUpdated(
            profileId: $this->getFirstProfileId($ft),
            oldData: $oldData,
            newData: $ft->getAttributes(),
            remarks: 'Updated disbursement: ' . ($validated['payee'] ?? $ft->payee_name),
        );

        $profileId = $validated['profile_id'] ?? $this->getFirstProfileId($ft);
        return response()->json([
            'success'      => true,
            'message'      => 'Disbursement updated successfully',
            'disbursement' => $this->normalizeFundTransaction($ft, $profileId),
        ]);
    }

    private function updateLegacyDisbursement(Request $request, $id)
    {
        $exemptStatuses = ['LOA', 'IRREGULAR', 'TRANSFERRED'];
        $isExempt = in_array($request->obr_status, $exemptStatuses);

        $validated = $request->validate([
            'disbursement_type' => $isExempt ? 'nullable|in:regular,reimbursement,financial_assistance' : 'required|in:regular,reimbursement,financial_assistance',
            'payee'             => $isExempt ? 'nullable|string|max:255' : 'required|string|max:255',
            'obr_no'            => 'nullable|string|max:255',
            'obr_status'        => 'nullable|string|max:100',
            'date_obligated'    => 'nullable|date',
            'year_level'        => 'nullable|string|max:255',
            'semester'          => 'nullable|string|max:255',
            'academic_year'     => 'nullable|string|max:255',
            'amount'            => $isExempt ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'remarks'           => 'nullable|string',
        ]);

        $disbursement = Disbursement::findOrFail($id);
        $oldData = $disbursement->getAttributes();
        $disbursement->update($validated);

        ActivityLogService::logRecordUpdated(
            profileId: $disbursement->profile_id,
            oldData: $oldData,
            newData: $disbursement->fresh()->getAttributes(),
            remarks: 'Updated disbursement: ' . ($validated['payee'] ?? ''),
        );

        return response()->json([
            'success'      => true,
            'message'      => 'Disbursement updated successfully',
            'disbursement' => $disbursement->load(['cheques', 'creator']),
        ]);
    }

    private function destroyFundTransaction(int $ftId)
    {
        $ft = FundTransaction::findOrFail($ftId);
        $profileId = $this->getFirstProfileId($ft);

        ActivityLogService::logRecordDeleted(
            profileId: $profileId,
            recordData: $ft->getAttributes(),
            remarks: 'Deleted disbursement: ' . ($ft->payee_name ?? '') . ' - ' . ($ft->amount ?? 0),
        );

        $ft->delete();
        return response()->json(['success' => true, 'message' => 'Disbursement deleted successfully']);
    }

    private function destroyLegacyDisbursement($id)
    {
        $disbursement = Disbursement::findOrFail($id);
        $disbursementData = $disbursement->getAttributes();
        $disbursement->delete();

        ActivityLogService::logRecordDeleted(
            profileId: $disbursementData['profile_id'],
            recordData: $disbursementData,
            remarks: 'Deleted disbursement: ' . ($disbursementData['payee'] ?? ''),
        );

        return response()->json(['success' => true, 'message' => 'Disbursement deleted successfully']);
    }

    // NORMALIZATION

    private function normalizeFundTransaction(FundTransaction $ft, $profileId): array
    {
        $profileIdInt = (int) $profileId;
        $amount = $ft->amount;
        if ($ft->scholar_ids) {
            foreach ($ft->scholar_ids as $scholar) {
                if (is_array($scholar) && ((int) ($scholar['profile_id'] ?? null)) === $profileIdInt) {
                    $amount = $scholar['amount'] ?? $ft->amount;
                    break;
                }
            }
        }

        return [
            'disbursement_id'     => 'FT-' . $ft->id,
            'fund_transaction_id' => $ft->id,
            'transaction_id'      => $ft->transaction_id,
            'disbursement_type'   => $this->fromObrType($ft->obr_type ?? $ft->disbursement_type),
            'payee'               => $ft->payee_name,
            'obr_no'              => $ft->obr_no,
            'obr_status'          => $ft->transaction_status ? strtoupper($ft->transaction_status) : null,
            'date_obligated'      => $ft->date_obligated?->format('Y-m-d') ?? $ft->created_at?->format('Y-m-d'),
            'year_level'          => $ft->year_level,
            'semester'            => $ft->semester,
            'academic_year'       => $ft->academic_year,
            'amount'              => (float) $amount,
            'remarks'             => $ft->remarks,
            'cheques'             => $ft->cheques ?? collect(),
            'attachments'         => $this->normalizeDocuments($ft->documents ?? collect(), $ft->id),
            'profile'             => null,
            'is_legacy'           => false,
        ];
    }

    private function normalizeLegacyDisbursement(Disbursement $d): array
    {
        return [
            'disbursement_id'     => $d->disbursement_id,
            'fund_transaction_id' => null,
            'transaction_id'      => null,
            'disbursement_type'   => $d->disbursement_type,
            'payee'               => $d->payee,
            'obr_no'              => $d->obr_no,
            'obr_status'          => $d->obr_status,
            'date_obligated'      => $d->date_obligated?->format('Y-m-d'),
            'year_level'          => $d->year_level,
            'semester'            => $d->semester,
            'academic_year'       => $d->academic_year,
            'amount'              => (float) $d->amount,
            'remarks'             => $d->remarks,
            'cheques'             => $d->cheques ?? [],
            'attachments'         => $d->attachments ?? [],
            'profile'             => $d->profile ? ['scholarship_grant' => $d->profile->scholarshipGrant ?? []] : null,
            'is_legacy'           => true,
        ];
    }

    private function normalizeDocuments($documents, int $ftId): array
    {
        return collect($documents)->map(fn($doc) => $this->normalizeDocument($doc, $ftId))->values()->toArray();
    }

    private function normalizeDocument(FundTransactionDocument $doc, int $ftId): array
    {
        return [
            'attachment_id'   => 'FTD-' . $doc->id,
            'disbursement_id' => 'FT-' . $ftId,
            'attachment_type' => $doc->document_type,
            'file_name'       => $doc->filename,
            'file_path'       => $doc->path,
            'file_type'       => $doc->mime_type,
            'file_size'       => $doc->file_size,
        ];
    }

    private function toObrType(?string $disbursementType): string
    {
        return match ($disbursementType) {
            'reimbursement'        => 'REIMBURSEMENT',
            'financial_assistance' => 'FINANCIAL ASSISTANCE',
            default                => 'REGULAR',
        };
    }

    private function fromObrType(?string $obrType): string
    {
        return match (strtoupper((string) $obrType)) {
            'REIMBURSEMENT'        => 'reimbursement',
            'FINANCIAL ASSISTANCE' => 'financial_assistance',
            default                => 'regular',
        };
    }

    private function getFirstProfileId(?FundTransaction $ft): mixed
    {
        if (!$ft || empty($ft->scholar_ids)) {
            return null;
        }
        $first = $ft->scholar_ids[0] ?? null;
        return is_array($first) ? ($first['profile_id'] ?? null) : $first;
    }
}
