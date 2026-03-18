<?php

namespace App\Services;

use App\Models\ScholarshipRecord;
use App\Models\ScholarshipRecordAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ScholarshipRecordFileService
{
    public function __construct(
        private FileUploadService $fileUploadService
    ) {}

    /**
     * Store scholarship record attachment after file processing
     * 
     * @param ScholarshipRecord $record
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $attachmentName (description of attachment)
     * @param int|null $pageNumber (optional, for contracts)
     * @return ScholarshipRecordAttachment
     * @throws \Exception
     */
    public function storeAttachment(
        ScholarshipRecord $record,
        $file,
        string $attachmentName,
        ?int $pageNumber = null
    ): ScholarshipRecordAttachment
    {
        // Process file through service
        $fileResult = $this->fileUploadService->processUpload($file);

        if (!$fileResult->isSuccess()) {
            Log::error('scholarship_record_file_processing_failed', [
                'record_id' => $record->id,
                'error' => $fileResult->getErrorMessage(),
            ]);
            throw new \Exception('File processing failed: ' . $fileResult->getErrorMessage());
        }

        // Get profile info for storage path
        $profile = $record->profile;
        $uniqueId = $profile->unique_id;
        $scholarName = preg_replace('/[^A-Za-z0-9_]/', '_', 
            $profile->first_name . '_' . $profile->last_name
        );

        // Clean attachment name
        $cleanAttachmentName = preg_replace('/[^A-Za-z0-9_]/', '_', $attachmentName);

        // Build filename with metadata
        $timestamp = date('YmdHis');
        $pageNumberSuffix = '';
        if (strtolower($attachmentName) === 'contract' && $pageNumber) {
            $pageNumberSuffix = '_page_' . $pageNumber;
        }

        $fileName = "scholarship_record_{$scholarName}_{$cleanAttachmentName}_{$timestamp}{$pageNumberSuffix}.{$this->getExtension($fileResult)}";
        $filePath = "attachments/{$uniqueId}/" . $fileName;

        // Store file in storage
        Storage::disk('public')->put($filePath, $fileResult->content);

        // Create attachment record
        $attachment = ScholarshipRecordAttachment::create([
            'scholarship_record_id' => $record->id,
            'attachment_name' => $attachmentName,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $fileResult->mimeType,
            'file_size' => $fileResult->compressedSize,
            'original_size' => $fileResult->originalSize,
            'compression_ratio' => $fileResult->compressionRatio,
            'page_number' => $pageNumber,
        ]);

        // Update record with attachment count
        $record->update([
            'has_attachments' => true,
        ]);

        Log::info('scholarship_record_file_uploaded', [
            'record_id' => $record->id,
            'attachment_id' => $attachment->id,
            'attachment_name' => $attachmentName,
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
