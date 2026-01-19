<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
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
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,exe|max:25600', // 25MB max
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
            // Fix image orientation based on EXIF data
            if (function_exists('exif_read_data')) {
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

            // Successfully loaded as image - optimize it
            $width = imagesx($image);
            $height = imagesy($image);

            // Force portrait orientation (height > width)
            if ($width > $height) {
                // Rotate landscape to portrait
                $image = imagerotate($image, 90, 0);
                $temp = $width;
                $width = $height;
                $height = $temp;
            }

            // Aggressive resize - max 1280px height for high compression
            $maxDimension = 1280;
            if ($height > $maxDimension) {
                $newHeight = $maxDimension;
                $newWidth = intval($width * ($maxDimension / $height));

                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resizedImage;
            }

            // High compression JPEG - 40% quality for maximum size reduction
            ob_start();
            imagejpeg($image, null, 40);
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

        // Get updated attachments list
        $disbursement = Disbursement::with('attachments')->findOrFail($disbursementId);

        return response()->json([
            'message' => 'Attachment uploaded successfully',
            'attachment' => $attachment,
            'attachments' => $disbursement->attachments,
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
        $disbursementId = $attachment->disbursement_id;

        // Delete file from storage
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        // Get updated attachments list
        $disbursement = Disbursement::with('attachments')->findOrFail($disbursementId);

        return response()->json([
            'message' => 'Attachment deleted successfully',
            'attachments' => $disbursement->attachments
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
