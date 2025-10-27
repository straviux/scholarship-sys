<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\DisbursementAttachment;
use App\Models\ScholarshipRecord;
use App\Models\ScholarshipRecordAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MobileUploadController extends Controller
{
    /**
     * Show the mobile upload page for disbursement
     */
    public function showDisbursementUpload($token)
    {
        $disbursement = Disbursement::where('upload_token', $token)
            ->where('upload_token_expires_at', '>', now())
            ->with('profile')
            ->first();

        if (!$disbursement) {
            return view('mobile.upload-expired');
        }

        return view('mobile.disbursement-upload', compact('disbursement'));
    }

    /**
     * Show the mobile upload page for scholarship record
     */
    public function showScholarshipRecordUpload($token)
    {
        $scholarshipRecord = ScholarshipRecord::where('upload_token', $token)
            ->where('upload_token_expires_at', '>', now())
            ->with('profile')
            ->first();

        if (!$scholarshipRecord) {
            return view('mobile.upload-expired');
        }

        return view('mobile.scholarship-record-upload', compact('scholarshipRecord'));
    }

    /**
     * Handle file upload for disbursement
     */
    public function uploadDisbursementFile(Request $request, $token)
    {
        $disbursement = Disbursement::where('upload_token', $token)
            ->where('upload_token_expires_at', '>', now())
            ->first();

        if (!$disbursement) {
            return response()->json(['error' => 'Invalid or expired token'], 403);
        }

        try {
            $request->validate([
                'attachment_type' => 'required|string|max:255',
                'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:25600', // 25MB max
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

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

            // Convert to JPEG with quality 60%
            ob_start();
            imagejpeg($image, null, 60);
            $processedContent = ob_get_clean();
            imagedestroy($image);
            $extension = '.jpg';
        } else {
            // PDF - use gzip compression
            $processedContent = gzencode($fileContent, 9);
            $extension = '.gz';
        }

        // Generate file path
        $fileName = time() . '_' . str_replace([' ', '.'], '_', pathinfo($originalFileName, PATHINFO_FILENAME)) . $extension;
        $filePath = 'disbursement_attachments/' . $fileName;

        // Store the file
        Storage::disk('public')->put($filePath, $processedContent);

        // Create attachment record
        $attachment = DisbursementAttachment::create([
            'disbursement_id' => $disbursement->disbursement_id,
            'attachment_type' => $request->attachment_type,
            'file_name' => $originalFileName,
            'file_path' => $filePath,
            'file_type' => $mimeType,
            'file_size' => strlen($processedContent),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'attachment' => $attachment,
            'original_size' => $originalSize,
            'optimized_size' => strlen($processedContent),
            'size_reduction' => round((1 - strlen($processedContent) / $originalSize) * 100, 2) . '%',
        ]);
    }

    /**
     * Handle file upload for scholarship record
     */
    public function uploadScholarshipRecordFile(Request $request, $token)
    {
        $scholarshipRecord = ScholarshipRecord::where('upload_token', $token)
            ->where('upload_token_expires_at', '>', now())
            ->first();

        if (!$scholarshipRecord) {
            return response()->json(['error' => 'Invalid or expired token'], 403);
        }

        try {
            $request->validate([
                'attachment_name' => 'required|string|max:255',
                'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:25600', // 25MB max
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

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

            // Convert to JPEG with quality 60%
            ob_start();
            imagejpeg($image, null, 60);
            $processedContent = ob_get_clean();
            imagedestroy($image);
            $extension = '.jpg';
        } else {
            // PDF - use gzip compression
            $processedContent = gzencode($fileContent, 9);
            $extension = '.gz';
        }

        // Generate file path
        $fileName = time() . '_' . str_replace([' ', '.'], '_', pathinfo($originalFileName, PATHINFO_FILENAME)) . $extension;
        $filePath = 'scholarship_record_attachments/' . $fileName;

        // Store the file
        Storage::disk('public')->put($filePath, $processedContent);

        // Create attachment record
        $attachment = ScholarshipRecordAttachment::create([
            'scholarship_record_id' => $scholarshipRecord->id,
            'attachment_name' => $request->attachment_name,
            'file_name' => $originalFileName,
            'file_path' => $filePath,
            'file_type' => $mimeType,
            'file_size' => strlen($processedContent),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'attachment' => $attachment,
            'original_size' => $originalSize,
            'optimized_size' => strlen($processedContent),
            'size_reduction' => round((1 - strlen($processedContent) / $originalSize) * 100, 2) . '%',
        ]);
    }
}
