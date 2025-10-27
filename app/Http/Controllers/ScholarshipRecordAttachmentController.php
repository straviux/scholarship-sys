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
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:25600', // 25MB max
        ]);

        $file = $request->file('file');
        $originalFileName = $file->getClientOriginalName();
        $originalSize = $file->getSize();
        $mimeType = $file->getClientMimeType();

        $fileContent = file_get_contents($file->getRealPath());
        $processedContent = $fileContent;
        $extension = '';

        // Try to load as image
        $image = @imagecreatefromstring($fileContent);

        if ($image !== false) {
            // Successfully loaded as image - optimize it
            $width = imagesx($image);
            $height = imagesy($image);

            // Resize if too large (max 1920px width/height)
            $maxDimension = 1920;
            if ($width > $maxDimension || $height > $maxDimension) {
                if ($width > $height) {
                    $newWidth = $maxDimension;
                    $newHeight = intval($height * ($maxDimension / $width));
                } else {
                    $newHeight = $maxDimension;
                    $newWidth = intval($width * ($maxDimension / $height));
                }

                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resizedImage;
            }

            // Always save as JPEG with compression (best size reduction)
            ob_start();
            imagejpeg($image, null, 60); // 60% quality for significant size reduction
            $processedContent = ob_get_clean();
            imagedestroy($image);
            $mimeType = 'image/jpeg';
        } elseif ($mimeType === 'application/pdf') {
            // Compress PDFs with gzip
            $processedContent = gzencode($fileContent, 9);
            $extension = '.gz';
        }

        // Store processed file
        $fileName = time() . '_' . $originalFileName . $extension;
        $filePath = 'scholarship_records/attachments/' . $fileName;
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

        return response()->json([
            'success' => true,
            'message' => 'Attachment uploaded successfully',
            'attachment' => $attachment,
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

        // Delete file from storage
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attachment deleted successfully'
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
            'expires_at' => $scholarshipRecord->upload_token_expires_at,
        ]);
    }
}
