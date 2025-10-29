<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            // 'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Update the user's profile name.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->update([
            'name' => $request->name,
        ]);

        return Redirect::back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Update the user's profile photo.
     */
    public function updatePhoto(Request $request): RedirectResponse
    {
        // Debug: Log the request details
        Log::info('Photo upload request received', [
            'has_file' => $request->hasFile('photo'),
            'files' => $request->allFiles(),
            'all_input' => $request->all()
        ]);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240'], // Increased max size to 10MB for processing
        ]);

        $user = $request->user();

        try {
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $originalSize = $photo->getSize();

                // Delete old profile photo if exists
                if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                    Storage::disk('public')->delete($user->profile_photo);
                    Log::info('Deleted old profile photo: ' . $user->profile_photo);
                }

                // Generate unique filename
                $filename = 'profile-' . $user->id . '-' . time() . '.jpg'; // Always save as JPG for better compression

                // Process and compress the image
                $processedImagePath = $this->processAndCompressImage($photo, $filename);

                // Update user record
                $user->update(['profile_photo' => $processedImagePath]);

                // Get the final file size for logging
                $finalSize = Storage::disk('public')->size($processedImagePath);
                $compressionRatio = round((1 - ($finalSize / $originalSize)) * 100, 2);

                Log::info('Photo upload successful', [
                    'user_id' => $user->id,
                    'filename' => $filename,
                    'path' => $processedImagePath,
                    'original_size' => $originalSize,
                    'final_size' => $finalSize,
                    'compression_ratio' => $compressionRatio . '%',
                    'original_mime_type' => $photo->getMimeType()
                ]);

                return Redirect::back()->with('success', 'Profile photo updated successfully!');
            }
        } catch (\Exception $e) {
            Log::error('Photo upload failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Redirect::back()->withErrors(['photo' => 'Failed to upload photo. Please try again.']);
        }

        return Redirect::back()->withErrors(['photo' => 'No photo file was uploaded.']);
    }

    /**
     * Process and compress the uploaded image
     */
    private function processAndCompressImage($uploadedFile, $filename): string
    {
        // Create image resource from uploaded file
        $imageResource = $this->createImageResource($uploadedFile);

        if (!$imageResource) {
            throw new \Exception('Failed to create image resource from uploaded file');
        }

        // Get original dimensions
        $originalWidth = imagesx($imageResource);
        $originalHeight = imagesy($imageResource);

        // Calculate new dimensions (max 400x400 for profile photos)
        $maxSize = 400;
        $ratio = min($maxSize / $originalWidth, $maxSize / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);

        // Create new image with calculated dimensions
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Set white background for transparency
        $white = imagecolorallocate($newImage, 255, 255, 255);
        imagefill($newImage, 0, 0, $white);

        // Resize image with high quality
        imagecopyresampled(
            $newImage,
            $imageResource,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        // Save to temporary location
        $tempPath = sys_get_temp_dir() . '/' . $filename;

        // Save as JPEG with 85% quality for good compression
        imagejpeg($newImage, $tempPath, 85);

        // Clean up memory
        imagedestroy($imageResource);
        imagedestroy($newImage);

        // Store the processed image
        $storagePath = 'profile-photos/' . $filename;
        Storage::disk('public')->put($storagePath, file_get_contents($tempPath));

        // Clean up temporary file
        unlink($tempPath);

        return $storagePath;
    }

    /**
     * Create image resource from uploaded file based on MIME type
     */
    private function createImageResource($uploadedFile)
    {
        $tempPath = $uploadedFile->getPathname();
        $mimeType = $uploadedFile->getMimeType();

        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                return imagecreatefromjpeg($tempPath);
            case 'image/png':
                return imagecreatefrompng($tempPath);
            case 'image/gif':
                return imagecreatefromgif($tempPath);
            case 'image/webp':
                return imagecreatefromwebp($tempPath);
            default:
                return false;
        }
    }

    /**
     * Generate QR code for mobile profile photo upload.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateQrCode(Request $request)
    {
        try {
            $user = $request->user();

            // Generate upload token (30 days expiry)
            $token = $user->generateUploadToken(43200);

            return response()->json([
                'success' => true,
                'token' => $token,
                'url' => $user->getMobileUploadUrl(),
                'qr_code_svg' => $user->getUploadQrCode(300),
                'qr_code_data_uri' => $user->getUploadQrCodeDataUri(300),
                'expires_at' => $user->upload_token_expires_at->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate QR code for profile upload', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code'
            ], 500);
        }
    }

    /**
     * Show mobile upload page for profile photo.
     *
     * @param string $token
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showMobileUpload($token)
    {
        // Find user by upload token
        $user = \App\Models\User::where('upload_token', $token)->first();

        if (!$user) {
            return view('mobile.upload-error', [
                'message' => 'Invalid upload link. Please request a new QR code.'
            ]);
        }

        // Check if token has expired
        if ($user->upload_token_expires_at < now()) {
            return view('mobile.upload-error', [
                'message' => 'Upload link has expired. Please request a new QR code.'
            ]);
        }

        return view('mobile.profile-upload', [
            'token' => $token,
            'user' => $user,
            'expiresAt' => $user->upload_token_expires_at,
        ]);
    }

    /**
     * Process mobile profile photo upload.
     *
     * @param Request $request
     * @param string $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function processMobileUpload(Request $request, $token)
    {
        try {
            // Find user by upload token
            $user = \App\Models\User::where('upload_token', $token)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid upload token'
                ], 404);
            }

            // Check if token has expired
            if ($user->upload_token_expires_at < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload token has expired'
                ], 401);
            }

            // Validate the uploaded file
            $request->validate([
                'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240'],
            ]);

            // Process the upload
            $photo = $request->file('photo');
            $originalSize = $photo->getSize();

            // Delete old profile photo if exists
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
                Log::info('Deleted old profile photo: ' . $user->profile_photo);
            }

            // Generate unique filename
            $filename = 'profile-' . $user->id . '-' . time() . '.jpg';

            // Process and compress the image
            $processedImagePath = $this->processAndCompressImage($photo, $filename);

            // Update user record
            $user->update(['profile_photo' => $processedImagePath]);

            // Get the final file size for logging
            $finalSize = Storage::disk('public')->size($processedImagePath);
            $compressionRatio = round((1 - ($finalSize / $originalSize)) * 100, 2);

            Log::info('Mobile photo upload successful', [
                'user_id' => $user->id,
                'filename' => $filename,
                'path' => $processedImagePath,
                'original_size' => $originalSize,
                'final_size' => $finalSize,
                'compression_ratio' => $compressionRatio . '%',
                'original_mime_type' => $photo->getMimeType()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile photo uploaded successfully',
                'photo_url' => asset('storage/' . $processedImagePath)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Mobile photo upload failed', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photo. Please try again.'
            ], 500);
        }
    }
}
