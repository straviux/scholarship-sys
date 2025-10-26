<?php

namespace App\Http\Controllers;

use App\Models\DisbursementAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DisbursementAttachmentController extends Controller
{
    /**
     * Upload a new attachment for a disbursement.
     */
    public function upload(Request $request, $disbursementId)
    {
        $request->validate([
            'attachment_type' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $originalFileName = $file->getClientOriginalName();
        $originalSize = $file->getSize();
        $mimeType = $file->getMimeType();

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
        $filePath = 'disbursements/attachments/' . $fileName;
        Storage::disk('public')->put($filePath, $processedContent);

        $finalSize = strlen($processedContent);

        $attachment = DisbursementAttachment::create([
            'disbursement_id' => $disbursementId,
            'attachment_type' => $request->attachment_type,
            'file_name' => $originalFileName,
            'file_path' => $filePath,
            'file_type' => $mimeType,
            'file_size' => $finalSize,
        ]);

        return response()->json([
            'message' => 'Attachment uploaded successfully',
            'attachment' => $attachment,
            'original_size' => $originalSize,
            'optimized_size' => $finalSize,
            'size_reduction' => round((1 - $finalSize / $originalSize) * 100, 2) . '%',
            'mime_detected' => $file->getMimeType()
        ], 201);
    }

    /**
     * Delete an attachment.
     */
    public function delete($attachmentId)
    {
        $attachment = DisbursementAttachment::findOrFail($attachmentId);

        // Delete file from storage
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json([
            'message' => 'Attachment deleted successfully'
        ]);
    }

    /**
     * Download an attachment.
     */
    public function download($attachmentId)
    {
        $attachment = DisbursementAttachment::findOrFail($attachmentId);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File not found');
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
     * View an attachment (for preview).
     */
    public function view($attachmentId)
    {
        $attachment = DisbursementAttachment::findOrFail($attachmentId);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File not found');
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
}
