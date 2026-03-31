<?php

namespace App\Http\Controllers;

use App\Models\ScholarshipRecord;
use App\Models\ScholarshipRecordAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ScholarshipRecordAttachmentController extends Controller
{
    /**
     * Upload attachment for scholarship record
     */
    public function upload(Request $request, $scholarshipRecordId)
    {
        $validated = $request->validate([
            'attachment_name' => 'required|string|max:255',
            'page_number' => 'nullable|integer|min:1',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,exe|max:25600', // 25MB max
        ]);

        // Get scholarship record with profile
        $scholarshipRecord = ScholarshipRecord::with('profile')->findOrFail($scholarshipRecordId);

        // Get scholar unique_id
        $profile = $scholarshipRecord->profile;
        $uniqueId = $profile->unique_id;

        // Get scholar name for filename
        $scholarName = $profile->first_name . '_' . $profile->last_name;
        // Clean scholar name (remove spaces, special characters)
        $scholarName = preg_replace('/[^A-Za-z0-9_]/', '_', $scholarName);

        // Get attachment name (clean it)
        $attachmentName = preg_replace('/[^A-Za-z0-9_]/', '_', $validated['attachment_name']);

        // Create short timestamp (YmdHis format)
        $timestamp = date('YmdHis');

        // Add page number suffix for contracts
        $pageNumberSuffix = '';
        if (strtolower($validated['attachment_name']) === 'contract' && isset($validated['page_number'])) {
            $pageNumberSuffix = '_page_' . $validated['page_number'];
        }

        $file = $request->file('file');
        $originalFileName = $file->getClientOriginalName();
        $originalSize = $file->getSize();
        $mimeType = $file->getClientMimeType();

        $fileContent = file_get_contents($file->getRealPath());
        $processedContent = $fileContent;
        $extension = '';

        // Load image settings from admin configuration (DB-first, config fallback)
        $imgSettings    = \App\Models\MobileUploadSetting::getCurrent()['image'];
        $jpegQuality    = $imgSettings['jpeg_quality']             ?? 60;
        $maxWidth       = $imgSettings['max_width']                ?? 1920;
        $maxHeight      = $imgSettings['max_height']               ?? 1920;
        $autoRotate     = $imgSettings['auto_rotate']              ?? true;
        $preserveFormat = $imgSettings['preserve_original_format'] ?? true;

        // Determine source format for format-preservation logic
        $sourceMime = $file->getMimeType();
        $isPng      = $sourceMime === 'image/png';
        $isGif      = $sourceMime === 'image/gif';
        $keepFormat = $preserveFormat && ($isPng || $isGif);

        // Try to load as image
        $image = @imagecreatefromstring($fileContent);

        if ($image !== false) {
            // Fix EXIF orientation (only relevant for JPEG/camera photos)
            if ($autoRotate && !$isPng && !$isGif && function_exists('exif_read_data')) {
                $exif = @exif_read_data($file->getRealPath());
                if ($exif && isset($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 3:
                            $image = imagerotate($image, 180, 0);
                            break;
                        case 6:
                            $image = imagerotate($image, -90, 0);
                            break;
                        case 8:
                            $image = imagerotate($image, 90, 0);
                            break;
                    }
                }
            }

            $width  = imagesx($image);
            $height = imagesy($image);

            // Force portrait orientation for JPEG/camera photos (not documents/screenshots)
            if (!$keepFormat && $width > $height) {
                $image = imagerotate($image, 90, 0);
                [$width, $height] = [$height, $width];
            }

            // Resize if exceeds max dimensions (maintain aspect ratio)
            if ($width > $maxWidth || $height > $maxHeight) {
                $ratio     = min($maxWidth / $width, $maxHeight / $height);
                $newWidth  = (int) round($width  * $ratio);
                $newHeight = (int) round($height * $ratio);

                $resized = imagecreatetruecolor($newWidth, $newHeight);
                if ($isPng && $keepFormat) {
                    // Preserve alpha channel transparency for PNG
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                    imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
                }
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
            }

            // Encode to appropriate format
            ob_start();
            if ($isPng && $keepFormat) {
                imagepng($image, null, 9);   // Lossless — level 9 = maximum compression
                $mimeType = 'image/png';
            } elseif ($isGif && $keepFormat) {
                imagegif($image);
                $mimeType = 'image/gif';
            } else {
                imagejpeg($image, null, $jpegQuality);
                $mimeType = 'image/jpeg';
            }
            $processedContent = ob_get_clean();
            imagedestroy($image);
        } elseif ($mimeType === 'application/pdf') {
            // Compress PDFs with gzip
            $processedContent = gzencode($fileContent, 9);
            $extension = '.gz';
        }

        // Get file extension from original filename or determine from mime type
        $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        if (empty($fileExtension)) {
            // Derive extension from the actual output mime type
            if ($mimeType === 'image/png') {
                $fileExtension = 'png';
            } elseif ($mimeType === 'image/gif') {
                $fileExtension = 'gif';
            } elseif (strpos($mimeType, 'image/') === 0) {
                $fileExtension = 'jpg';
            } elseif ($mimeType === 'application/pdf') {
                $fileExtension = 'pdf';
            }
        }

        // Create new filename: scholarship_record_[scholar_name]_[attachment_name]_[timestamp][page_suffix].[extension]
        $fileName = "scholarship_record_{$scholarName}_{$attachmentName}_{$timestamp}{$pageNumberSuffix}" . ($extension ?: ".{$fileExtension}");

        // Store file in: attachments/[unique_id]/
        $filePath = "attachments/{$uniqueId}/" . $fileName;
        Storage::disk('public')->put($filePath, $processedContent);

        $finalSize = strlen($processedContent);

        $attachment = ScholarshipRecordAttachment::create([
            'scholarship_record_id' => $scholarshipRecordId,
            'attachment_name' => $validated['attachment_name'],
            'file_name' => $originalFileName,
            'file_path' => $filePath,
            'file_type' => $mimeType,
            'file_size' => $finalSize,
        ]);

        // Get updated attachments list
        $scholarshipRecord = ScholarshipRecord::with('attachments')->findOrFail($scholarshipRecordId);

        return response()->json([
            'success' => true,
            'message' => 'Attachment uploaded successfully',
            'attachment' => $attachment,
            'attachments' => $scholarshipRecord->attachments,
            'original_size' => $originalSize,
            'optimized_size' => $finalSize,
            'size_reduction' => round((1 - $finalSize / $originalSize) * 100, 2) . '%',
            'mime_detected' => $file->getClientMimeType()
        ]);
    }
    /**
     * Delete attachment
     */
    public function delete($attachmentId)
    {
        $attachment = ScholarshipRecordAttachment::findOrFail($attachmentId);
        $scholarshipRecordId = $attachment->scholarship_record_id;

        // Delete file from storage
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        // Get updated attachments list
        $scholarshipRecord = ScholarshipRecord::with('attachments')->findOrFail($scholarshipRecordId);

        return response()->json([
            'success' => true,
            'message' => 'Attachment deleted successfully',
            'attachments' => $scholarshipRecord->attachments
        ]);
    }

    /**
     * Download attachment
     */
    public function download($attachmentId)
    {
        $attachment = ScholarshipRecordAttachment::findOrFail($attachmentId);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $fileContent = Storage::disk('public')->get($attachment->file_path);

        // Check if file is gzip compressed (PDFs)
        if (str_ends_with($attachment->file_path, '.gz')) {
            $fileContent = gzdecode($fileContent);
        }

        // Create temporary file for download
        $tempPath = storage_path('app/temp/' . $attachment->file_name);
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        file_put_contents($tempPath, $fileContent);

        return response()->download($tempPath, $attachment->file_name)->deleteFileAfterSend(true);
    }

    /**
     * View attachment (for inline preview)
     */
    public function view($attachmentId)
    {
        $attachment = ScholarshipRecordAttachment::findOrFail($attachmentId);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $fileContent = Storage::disk('public')->get($attachment->file_path);

        // Check if file is gzip compressed (PDFs)
        if (str_ends_with($attachment->file_path, '.gz')) {
            $fileContent = gzdecode($fileContent);
        }

        return response($fileContent)
            ->header('Content-Type', $attachment->file_type)
            ->header('Content-Disposition', 'inline; filename="' . $attachment->file_name . '"');
    }

    /**
     * Generate QR code for mobile upload
     */
    public function generateQrCode($scholarshipRecordId)
    {
        $scholarshipRecord = ScholarshipRecord::findOrFail($scholarshipRecordId);

        // Generate or refresh upload token
        $scholarshipRecord->generateUploadToken();

        return response()->json([
            'qr_code' => $scholarshipRecord->getUploadQrCode(250),
            'url' => $scholarshipRecord->getMobileUploadUrl(),
            'expires_at' => $scholarshipRecord->upload_token_expires_at?->toIso8601String(),
        ]);
    }
}
