<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\DisbursementAttachment;
use App\Models\ScholarshipRecord;
use App\Models\ScholarshipRecordAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MobileUploadController extends Controller
{
    /**
     * Show the mobile upload page for disbursement
     */
    public function showDisbursementUpload($token)
    {
        $disbursement = Disbursement::where('upload_token', $token)->first();

        if (!$disbursement) {
            Log::warning('Disbursement not found for token', ['token' => $token]);
            return view('mobile.upload-expired');
        }

        if (!$disbursement->upload_token_expires_at) {
            Log::warning('Disbursement has no expiry date', ['disbursement_id' => $disbursement->disbursement_id]);
            return view('mobile.upload-expired');
        }

        if ($disbursement->upload_token_expires_at->isPast()) {
            Log::info('Disbursement token expired', [
                'disbursement_id' => $disbursement->disbursement_id,
                'expires_at' => $disbursement->upload_token_expires_at,
                'now' => now(),
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
        // Clear any query cache
        DB::connection()->disableQueryLog();

        $scholarshipRecord = ScholarshipRecord::where('upload_token', $token)->first();

        if (!$scholarshipRecord) {
            Log::warning('Scholarship record not found for token', ['token' => substr($token, 0, 10) . '...']);
            return view('mobile.upload-expired');
        }

        if (!$scholarshipRecord->upload_token_expires_at) {
            Log::warning('Scholarship record has no expiry date', ['id' => $scholarshipRecord->id]);
            return view('mobile.upload-expired');
        }

        // Debug logging
        Log::info('Mobile upload page accessed', [
            'id' => $scholarshipRecord->id,
            'token' => substr($token, 0, 10) . '...',
            'expires_at' => $scholarshipRecord->upload_token_expires_at->toDateTimeString(),
            'expires_at_timestamp' => $scholarshipRecord->upload_token_expires_at->timestamp,
            'now' => now()->toDateTimeString(),
            'now_timestamp' => now()->timestamp,
            'diff_seconds' => now()->diffInSeconds($scholarshipRecord->upload_token_expires_at, false),
            'is_past' => $scholarshipRecord->upload_token_expires_at->isPast(),
            'is_future' => $scholarshipRecord->upload_token_expires_at->isFuture(),
        ]);

        if ($scholarshipRecord->upload_token_expires_at->isPast()) {
            Log::info('Scholarship record token expired', [
                'id' => $scholarshipRecord->id,
                'expires_at' => $scholarshipRecord->upload_token_expires_at,
                'now' => now(),
            ]);
            return view('mobile.upload-expired');
        }

        $scholarshipRecord->load('profile');

        return view('mobile.scholarship-record-upload', compact('scholarshipRecord'));
    }

    /**
     * Handle file upload for disbursement
     */
    public function uploadDisbursementFile(Request $request, $token)
    {
        $disbursement = Disbursement::where('upload_token', $token)->first();

        if (!$disbursement || $disbursement->upload_token_expires_at->isPast()) {
            return response()->json(['error' => 'Invalid or expired token'], 403);
        }

        // Debug: Log what we're receiving
        Log::info('Mobile upload request received', [
            'has_file' => $request->hasFile('file'),
            'all_files' => $request->allFiles(),
            'all_input' => $request->except('file'),
            'content_type' => $request->header('Content-Type'),
        ]);

        try {
            $request->validate([
                'attachment_type' => 'required|in:voucher,cheque,receipt',
                'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:25600', // 25MB max
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Mobile upload validation failed', [
                'errors' => $e->errors(),
                'file_info' => $request->hasFile('file') ? [
                    'size' => $request->file('file')->getSize(),
                    'mime' => $request->file('file')->getMimeType(),
                    'original_name' => $request->file('file')->getClientOriginalName(),
                ] : 'No file uploaded'
            ]);
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

            // Successfully loaded as image - highly compress it
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

            // Aggressive resize for mobile uploads - max 1280px height
            $maxDimension = 1280;
            if ($height > $maxDimension) {
                $newHeight = $maxDimension;
                $newWidth = intval($width * ($maxDimension / $height));

                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

                // Enable smooth scaling for better quality at lower file size
                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resizedImage;
            }

            // High compression JPEG - quality 40% for smaller file size
            ob_start();
            imagejpeg($image, null, 40);
            $processedContent = ob_get_clean();
            imagedestroy($image);
            $extension = '.jpg';
        } else {
            // PDF - use gzip compression
            $processedContent = gzencode($fileContent, 9);
            $extension = '.gz';
        }

        // Get scholar unique_id
        $profile = $disbursement->profile;
        $uniqueId = $profile->unique_id;

        // Get scholar name for filename
        $scholarName = $profile->first_name . '_' . $profile->last_name;
        // Clean scholar name (remove spaces, special characters)
        $scholarName = preg_replace('/[^A-Za-z0-9_]/', '_', $scholarName);

        // Get attachment type
        $attachmentType = $request->attachment_type;

        // Create short timestamp (YmdHis format)
        $timestamp = date('YmdHis');

        // Determine file extension
        if ($extension === '.jpg') {
            $fileExtension = 'jpg';
        } elseif ($extension === '.gz') {
            $fileExtension = 'pdf.gz';
        } else {
            $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        }

        // Generate file path: disbursement_[scholar_name]_[attachment_type]_[timestamp].[extension]
        $fileName = "disbursement_{$scholarName}_{$attachmentType}_{$timestamp}.{$fileExtension}";

        // Store file in: attachments/[unique_id]/
        $filePath = "attachments/{$uniqueId}/" . $fileName;

        // Store the file
        Storage::disk('public')->put($filePath, $processedContent);

        // Create attachment record
        $attachment = DisbursementAttachment::create([
            'disbursement_id' => $disbursement->disbursement_id,
            'attachment_type' => $request->attachment_type,
            'file_name' => $fileName, // Use formatted filename instead of original
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
        $scholarshipRecord = ScholarshipRecord::where('upload_token', $token)->first();

        if (!$scholarshipRecord || $scholarshipRecord->upload_token_expires_at->isPast()) {
            return response()->json(['error' => 'Invalid or expired token'], 403);
        }

        // Debug: Log what we're receiving
        Log::info('Mobile upload request received (scholarship)', [
            'has_file' => $request->hasFile('file'),
            'all_files' => $request->allFiles(),
            'all_input' => $request->except('file'),
            'content_type' => $request->header('Content-Type'),
        ]);

        try {
            $request->validate([
                'attachment_name' => 'required|string|max:255',
                'page_number' => 'nullable|integer|min:1',
                'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:25600', // 25MB max
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Mobile upload validation failed (scholarship record)', [
                'errors' => $e->errors(),
                'file_info' => $request->hasFile('file') ? [
                    'size' => $request->file('file')->getSize(),
                    'mime' => $request->file('file')->getMimeType(),
                    'original_name' => $request->file('file')->getClientOriginalName(),
                ] : 'No file uploaded'
            ]);
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

            // Successfully loaded as image - highly compress it
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

            // Aggressive resize for mobile uploads - max 1280px height
            $maxDimension = 1280;
            if ($height > $maxDimension) {
                $newHeight = $maxDimension;
                $newWidth = intval($width * ($maxDimension / $height));

                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

                // Enable smooth scaling for better quality at lower file size
                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resizedImage;
            }

            // High compression JPEG - quality 40% for smaller file size
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

        // Get scholar unique_id
        $profile = $scholarshipRecord->profile;
        $uniqueId = $profile->unique_id;

        // Get scholar name for filename
        $scholarName = $profile->first_name . '_' . $profile->last_name;
        // Clean scholar name (remove spaces, special characters)
        $scholarName = preg_replace('/[^A-Za-z0-9_]/', '_', $scholarName);

        // Get attachment name (clean it)
        $attachmentName = preg_replace('/[^A-Za-z0-9_]/', '_', $request->attachment_name);

        // Create short timestamp (YmdHis format)
        $timestamp = date('YmdHis');

        // Add page number suffix for contracts
        $pageNumberSuffix = '';
        if (strtolower($request->attachment_name) === 'contract' && $request->has('page_number')) {
            $pageNumberSuffix = '_page_' . $request->page_number;
        }

        // Get file extension from original filename or determine from mime type
        $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        if (empty($fileExtension)) {
            // Determine extension from mime type
            if (strpos($mimeType, 'image/') === 0) {
                $fileExtension = 'jpg';
            } elseif ($mimeType === 'application/pdf') {
                $fileExtension = 'pdf';
            }
        }

        // Create new filename: scholarship_record_[scholar_name]_[attachment_name]_[timestamp][page_suffix].[extension]
        $fileName = "scholarship_record_{$scholarName}_{$attachmentName}_{$timestamp}{$pageNumberSuffix}" . ($extension ?: ".{$fileExtension}");

        // Store file in: attachments/[unique_id]/
        $filePath = "attachments/{$uniqueId}/" . $fileName;

        // Store the file
        Storage::disk('public')->put($filePath, $processedContent);

        // Create attachment record
        $attachment = ScholarshipRecordAttachment::create([
            'scholarship_record_id' => $scholarshipRecord->id,
            'attachment_name' => $request->attachment_name,
            'file_name' => $fileName, // Use formatted filename instead of original
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
