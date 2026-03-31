<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserActivity;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    /**
     * Show the settings page
     */
    public function show()
    {
        $user = auth()->user();
        $user->load('roles');

        $userPreferences = $user->preferences ?? [
            'theme' => 'light',
            'language' => 'en',
            'email_notifications' => true,
            'maintenance_alerts' => true,
        ];

        return Inertia::render('User/Settings', [
            'user' => $user,
            'profile_photo_url' => $user->profile_photo_url,
            'has_profile_photo' => $user->hasProfilePhoto(),
            'preferences' => $userPreferences,
        ]);
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        UserActivity::log(
            auth()->id(),
            'change_password',
            'User changed their password'
        );

        return back()->with('success', 'Password changed successfully');
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:20',
            'office_designation' => 'nullable|string|max:255',
        ]);

        auth()->user()->update($validated);

        UserActivity::log(
            auth()->id(),
            'update_profile',
            'User updated their profile information'
        );

        return back()->with('success', 'Profile updated successfully');
    }

    /**
     * Update user profile photo
     */
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        $user = auth()->user();

        try {
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');

                // Delete old profile photo if exists
                // Normalize path — strip any accidental 'storage/' prefix stored by older code
                $oldPhoto = preg_replace('#^/?storage/#', '', $user->profile_photo ?? '');
                if ($oldPhoto && Storage::disk('public')->exists($oldPhoto)) {
                    Storage::disk('public')->delete($oldPhoto);
                }

                // Generate unique filename with format-appropriate extension
                $ext = match ($photo->getMimeType()) {
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    default     => 'jpg',
                };
                $filename = 'profile-' . $user->id . '-' . time() . '.' . $ext;

                // Process and compress the image
                $processedImagePath = $this->processAndCompressImage($photo, $filename);

                // Update user record
                $user->update(['profile_photo' => $processedImagePath]);

                UserActivity::log(
                    auth()->id(),
                    'update_profile_photo',
                    'User updated their profile photo'
                );

                return response()->json(['success' => true, 'message' => 'Profile photo updated successfully']);
            }

            return response()->json(['success' => false, 'message' => 'No file provided'], 400);
        } catch (\Exception $e) {
            Log::error('Profile photo upload failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to upload photo'], 500);
        }
    }

    /**
     * Process and compress image (max 400x400 for profile photos).
     * PNG and GIF are preserved in their original format; JPEG/WebP are saved as JPEG.
     */
    private function processAndCompressImage($uploadedFile, $filename): string
    {
        $sourceMime = $uploadedFile->getMimeType();
        $isPng      = $sourceMime === 'image/png';
        $isGif      = $sourceMime === 'image/gif';

        // GIFs must be stored as-is — GD only captures the first frame, destroying animation
        if ($isGif) {
            $storagePath = 'profile-photos/' . $filename;
            Storage::disk('public')->put($storagePath, file_get_contents($uploadedFile->getPathname()));
            return $storagePath;
        }

        // Create image resource from uploaded file
        $imageResource = $this->createImageResource($uploadedFile);

        if (!$imageResource) {
            throw new \Exception('Failed to create image resource from uploaded file');
        }

        // Get original dimensions
        $originalWidth  = imagesx($imageResource);
        $originalHeight = imagesy($imageResource);

        // Calculate new dimensions (max 400x400 for profile photos)
        $maxSize  = 400;
        $ratio    = min($maxSize / $originalWidth, $maxSize / $originalHeight);
        $newWidth  = (int)($originalWidth  * $ratio);
        $newHeight = (int)($originalHeight * $ratio);

        // Create new image canvas
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        if ($isPng) {
            // Preserve alpha channel transparency for PNG
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        } else {
            // White background for JPEG / GIF
            $white = imagecolorallocate($newImage, 255, 255, 255);
            imagefill($newImage, 0, 0, $white);
        }

        // Resize with high quality
        imagecopyresampled($newImage, $imageResource, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Save to temporary location in the correct format
        $tempPath = sys_get_temp_dir() . '/' . $filename;

        if ($isPng) {
            imagepng($newImage, $tempPath, 9);   // Lossless — level 9 = max compression
        } elseif ($isGif) {
            imagegif($newImage, $tempPath);
        } else {
            imagejpeg($newImage, $tempPath, 85);
        }

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
}
