<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\ScholarshipRecord;
use App\Models\ScholarshipProfileRequirement;
use App\Models\FundTransaction;
use App\Http\Requests\DisbursementUploadRequest;
use App\Http\Requests\ScholarshipRecordUploadRequest;
use App\Http\Requests\RequirementUploadRequest;
use App\Http\Requests\FundTransactionUploadRequest;
use App\Services\FileUploadService;
use App\Services\DisbursementFileService;
use App\Services\ScholarshipRecordFileService;
use App\Services\FundTransactionFileService;
use App\Traits\TokenValidation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MobileUploadController extends Controller
{
    use TokenValidation;

    public function __construct(
        private FileUploadService $fileUploadService,
        private DisbursementFileService $disbursementFileService,
        private ScholarshipRecordFileService $scholarshipRecordFileService,
        private FundTransactionFileService $fundTransactionFileService,
    ) {}

    /**
     * Show the mobile upload page for disbursement
     */
    public function showDisbursementUpload($token)
    {
        $disbursement = Disbursement::where('upload_token', $token)->first();

        if (!$disbursement) {
            Log::warning('disbursement_not_found', ['token' => substr($token, 0, 10) . '...']);
            return view('mobile.upload-expired');
        }

        try {
            $this->validateUploadToken($disbursement, 'disbursement');
        } catch (\Exception $e) {
            Log::info('disbursement_token_expired', [
                'disbursement_id' => $disbursement->disbursement_id,
                'error' => $e->getMessage(),
            ]);
            return view('mobile.upload-expired');
        }

        $disbursement->load('profile');

        return view('mobile.disbursement-upload', compact('disbursement'));
    }

    /**
     * Show the mobile upload page for scholarship record
     */
    public function showScholarshipRecordUpload($token)
    {
        $scholarshipRecord = ScholarshipRecord::where('upload_token', $token)->first();

        if (!$scholarshipRecord) {
            Log::warning('scholarship_record_not_found', ['token' => substr($token, 0, 10) . '...']);
            return view('mobile.upload-expired');
        }

        try {
            $this->validateUploadToken($scholarshipRecord, 'scholarship_record');
        } catch (\Exception $e) {
            Log::info('scholarship_record_token_expired', [
                'id' => $scholarshipRecord->id,
                'error' => $e->getMessage(),
            ]);
            return view('mobile.upload-expired');
        }

        $scholarshipRecord->load('profile');

        return view('mobile.scholarship-record-upload', compact('scholarshipRecord'));
    }

    /**
     * Handle file upload for disbursement
     */
    public function uploadDisbursementFile(DisbursementUploadRequest $request, $token)
    {
        try {
            // Find and validate disbursement
            $disbursement = Disbursement::where('upload_token', $token)->first();

            if (!$disbursement) {
                Log::warning('disbursement_not_found', ['token' => substr($token, 0, 10) . '...']);
                return $this->notFound('Disbursement not found');
            }

            // Validate token hasn't expired
            $this->validateUploadToken($disbursement, 'disbursement');

            // Process and store file
            $attachment = $this->disbursementFileService->storeAttachment(
                $disbursement,
                $request->file('file'),
                $request->input('attachment_type')
            );

            return $this->success([
                'file_id' => $attachment->attachment_id,
                'filename' => $attachment->file_name,
                'size' => $attachment->file_size,
                'original_size' => $attachment->original_size,
                'compression_ratio' => $attachment->compression_ratio . '%',
            ], 'File uploaded successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('disbursement_validation_failed', [
                'errors' => $e->errors(),
                'token' => substr($token, 0, 10) . '...',
            ]);
            return $this->unprocessable('Validation failed', $e->errors());
        } catch (\Exception $e) {
            Log::error('disbursement_upload_error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
            ]);
            return $this->error('Upload failed: ' . $e->getMessage(), code: 500);
        }
    }


    /**
     * Handle file upload for scholarship record
     */
    public function uploadScholarshipRecordFile(ScholarshipRecordUploadRequest $request, $token)
    {
        try {
            // Find and validate scholarship record
            $record = ScholarshipRecord::where('upload_token', $token)->first();

            if (!$record) {
                Log::warning('scholarship_record_not_found', ['token' => substr($token, 0, 10) . '...']);
                return $this->notFound('Scholarship record not found');
            }

            // Validate token hasn't expired
            $this->validateUploadToken($record, 'scholarship_record');

            // Process and store file
            $attachment = $this->scholarshipRecordFileService->storeAttachment(
                $record,
                $request->file('file'),
                $request->input('attachment_name'),
                $request->input('page_number')
            );

            return $this->success([
                'file_id' => $attachment->attachment_id,
                'filename' => $attachment->file_name,
                'size' => $attachment->file_size,
                'original_size' => $attachment->original_size,
                'compression_ratio' => $attachment->compression_ratio . '%',
            ], 'File uploaded successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('scholarship_record_validation_failed', [
                'errors' => $e->errors(),
                'token' => substr($token, 0, 10) . '...',
            ]);
            return $this->unprocessable('Validation failed', $e->errors());
        } catch (\Exception $e) {
            Log::error('scholarship_record_upload_error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
            ]);
            return $this->error('Upload failed: ' . $e->getMessage(), code: 500);
        }
    }


    /**
     * Show the mobile upload page for requirement
     */
    public function showRequirementUpload($token)
    {
        $requirement = ScholarshipProfileRequirement::where('upload_token', $token)->first();

        if (!$requirement) {
            Log::warning('requirement_not_found', ['token' => substr($token, 0, 10) . '...']);
            return view('mobile.upload-expired');
        }

        try {
            $this->validateUploadToken($requirement, 'requirement');
        } catch (\Exception $e) {
            Log::info('requirement_token_expired', [
                'id' => $requirement->id,
                'error' => $e->getMessage(),
            ]);
            return view('mobile.upload-expired');
        }

        $requirement->load('profile', 'requirement');

        return view('mobile.requirement-upload', compact('requirement'));
    }

    /**
     * Handle file upload for requirement
     */
    public function uploadRequirementFile(RequirementUploadRequest $request, $token)
    {
        try {
            // Find and validate requirement
            $requirement = ScholarshipProfileRequirement::where('upload_token', $token)
                ->with('profile', 'requirement')
                ->first();

            if (!$requirement) {
                Log::warning('requirement_not_found', ['token' => substr($token, 0, 10) . '...']);
                return $this->notFound('Requirement not found');
            }

            // Validate token hasn't expired
            $this->validateUploadToken($requirement, 'requirement');

            // Process file
            $result = $this->fileUploadService->processUpload($request->file('file'));

            if (!$result->isSuccess()) {
                Log::error('requirement_file_processing_failed', [
                    'requirement_id' => $requirement->id,
                    'error' => $result->getErrorMessage(),
                ]);
                return $this->error('File processing failed', code: 422);
            }

            // Store file and update requirement
            $profile = $requirement->profile;
            $uniqueId = $profile->unique_id;
            $scholarName = preg_replace(
                '/[^A-Za-z0-9_]/',
                '_',
                $profile->first_name . '_' . $profile->last_name
            );
            $requirementName = $requirement->requirement
                ? preg_replace('/[^A-Za-z0-9_]/', '_', $requirement->requirement->name)
                : 'requirement';

            $timestamp = date('YmdHis');
            $fileName = "requirement_{$scholarName}_{$requirementName}_{$timestamp}.{$result->extension}";
            $filePath = "attachments/{$uniqueId}/" . $fileName;

            // Store file
            \Illuminate\Support\Facades\Storage::disk('public')->put($filePath, $result->content);

            // Update requirement record
            $requirement->update([
                'file_name' => $fileName,
                'file_path' => \Illuminate\Support\Facades\Storage::url($filePath),
            ]);

            Log::info('requirement_file_uploaded', [
                'requirement_id' => $requirement->id,
                'profile_id' => $profile->id,
                'file_size' => $result->compressedSize,
                'compression_ratio' => $result->getCompressionRatio(),
            ]);

            return $this->success([
                'file_id' => $requirement->id,
                'filename' => $fileName,
                'size' => $result->compressedSize,
                'original_size' => $result->originalSize,
                'compression_ratio' => $result->getCompressionRatio(),
            ], 'File uploaded successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('requirement_validation_failed', [
                'errors' => $e->errors(),
                'token' => substr($token, 0, 10) . '...',
            ]);
            return $this->unprocessable('Validation failed', $e->errors());
        } catch (\Exception $e) {
            Log::error('requirement_upload_error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
            ]);
            return $this->error('Upload failed: ' . $e->getMessage(), code: 500);
        }
    }


    /**
     * Show the mobile upload page for fund transaction
     */
    public function showFundTransactionUpload($token, $docType = null)
    {
        $transaction = FundTransaction::where('upload_token', $token)->first();

        if (!$transaction) {
            Log::warning('fund_transaction_not_found', ['token' => substr($token, 0, 10) . '...']);
            return view('mobile.upload-expired');
        }

        try {
            $this->validateUploadToken($transaction, 'fund_transaction');
        } catch (\Exception $e) {
            Log::info('fund_transaction_token_expired', [
                'id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            return view('mobile.upload-expired');
        }

        // Validate document type if provided
        $validDocTypes = ['obr', 'dv_payroll', 'los', 'cheque'];
        if ($docType && !in_array($docType, $validDocTypes)) {
            $docType = null;
        }

        return view('mobile.fund-transaction-upload', compact('transaction', 'docType'));
    }

    /**
     * Handle file upload for fund transaction
     */
    public function uploadFundTransactionFile(FundTransactionUploadRequest $request, $token)
    {
        try {
            // Find transaction
            $transaction = FundTransaction::where('upload_token', $token)->first();

            if (!$transaction) {
                return $this->notFound('Fund transaction not found');
            }

            // Validate token hasn't expired
            $this->validateUploadToken($transaction, 'fund_transaction');

            // Store document using service
            $document = $this->fundTransactionFileService->storeDocument(
                $transaction,
                $request->file('file'),
                $request->input('document_type')
            );

            return $this->success([
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'filename' => $document->filename,
                'size' => $document->file_size,
                'original_size' => $document->original_file_size ?? $document->file_size,
            ], 'File uploaded successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('fund_transaction_validation_failed', [
                'errors' => $e->errors(),
                'token' => substr($token, 0, 10) . '...',
            ]);
            return $this->unprocessable('Validation failed', $e->errors());
        } catch (\Exception $e) {
            Log::error('fund_transaction_upload_error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
            ]);
            return $this->error('Upload failed: ' . $e->getMessage(), code: 500);
        }
    }
}
