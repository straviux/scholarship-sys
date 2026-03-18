<?php

namespace App\Services;

use App\Models\Disbursement;
use App\Models\DisbursementAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DisbursementFileService
{
    public function __construct(
        private FileUploadService $fileUploadService
    ) {}

    /**
     * Store disbursement attachment after file processing
     * 
     * @param Disbursement $disbursement
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $attachmentType (voucher, cheque, receipt)
     * @return DisbursementAttachment
     * @throws \Exception
     */
    public function storeAttachment(
        Disbursement $disbursement,
        $file,
        string $attachmentType
    ): DisbursementAttachment
    {
        // Process file through service
        $fileResult = $this->fileUploadService->processUpload($file);

        if (!$fileResult->isSuccess()) {
            Log::error('disbursement_file_processing_failed', [
                'disbursement_id' => $disbursement->disbursement_id,
                'error' => $fileResult->getErrorMessage(),
            ]);
            throw new \Exception('File processing failed: ' . $fileResult->getErrorMessage());
        }

        // Get profile info for storage path
        $profile = $disbursement->profile;
        $uniqueId = $profile->unique_id;
        $scholarName = preg_replace('/[^A-Za-z0-9_]/', '_', 
            $profile->first_name . '_' . $profile->last_name
        );

        // Build filename with metadata
        $timestamp = date('YmdHis');
        $fileName = "disbursement_{$scholarName}_{$attachmentType}_{$timestamp}.{$this->getExtension($fileResult)}";
        $filePath = "attachments/{$uniqueId}/" . $fileName;

        // Store file in storage
        Storage::disk('public')->put($filePath, $fileResult->content);

        // Create attachment record
        $attachment = DisbursementAttachment::create([
            'disbursement_id' => $disbursement->disbursement_id,
            'attachment_type' => $attachmentType,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $fileResult->mimeType,
            'file_size' => $fileResult->compressedSize,
            'original_size' => $fileResult->originalSize,
            'compression_ratio' => $fileResult->compressionRatio,
        ]);

        // Update disbursement record
        $disbursement->update([
            'has_attachment' => true,
            'last_attachment_date' => now(),
        ]);

        Log::info('disbursement_file_uploaded', [
            'disbursement_id' => $disbursement->disbursement_id,
            'attachment_id' => $attachment->id,
            'attachment_type' => $attachmentType,
            'file_size' => $fileResult->compressedSize,
            'compression_ratio' => $fileResult->getCompressionRatio(),
            'original_size' => $fileResult->originalSize,
        ]);

        return $attachment;
    }

    /**
     * Get file extension based on mime type
     */
    private function getExtension(FileProcessingResult $result): string
    {
        if (str_contains($result->mimeType, 'image')) {
            return 'jpg';
        } elseif (str_contains($result->mimeType, 'pdf')) {
            return 'pdf.gz';
        }
        return 'bin';
    }
}
