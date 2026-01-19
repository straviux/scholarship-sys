# QR Code Profile Photo Upload Implementation

## Overview

Successfully implemented QR code mobile upload functionality for user profile photos, following the same pattern as the existing disbursement and scholarship record mobile upload system.

## Implementation Date

October 29, 2025

## Components Implemented

### 1. Database Migration

**File:** `database/migrations/2025_10_29_082724_add_upload_token_to_users_table.php`

Added two columns to the `users` table:

- `upload_token` (string, 64 characters, unique, nullable)
- `upload_token_expires_at` (timestamp, nullable)

**Status:** ✅ Migrated successfully

### 2. User Model Enhancement

**File:** `app/Models/User.php`

Added the following methods:

- `generateUploadToken($expiresInMinutes = 43200)` - Generates 64-character token with 30-day expiry
- `getMobileUploadUrl()` - Returns mobile upload URL with token
- `getUploadQrCode($size = 200)` - Generates SVG QR code
- `getUploadQrCodeDataUri($size = 200)` - Generates base64 PNG QR code

Also added:

- Import statements for `Str` and `QrCode` facades
- Upload token fields to `$fillable` array
- Upload token expiration cast to datetime

### 3. ProfileController Enhancement

**File:** `app/Http/Controllers/ProfileController.php`

Added three new methods:

#### `generateQrCode(Request $request)`

- Generates QR code for authenticated users
- Returns JSON with token, URL, QR codes (SVG and data URI), and expiration
- Protected by auth middleware

#### `showMobileUpload($token)`

- Public route to display mobile upload page
- Validates token existence and expiration
- Returns `mobile.profile-upload` blade view or error page

#### `processMobileUpload(Request $request, $token)`

- Handles photo upload from mobile
- Validates token and file
- Uses existing `processAndCompressImage()` method
- Returns JSON response with success/error status

### 4. Routes

**File:** `routes/web.php`

Added public routes (no auth required):

```php
Route::get('/mobile/upload/profile/{token}', [ProfileController::class, 'showMobileUpload'])
    ->name('mobile.profile.upload');
Route::post('/mobile/upload/profile/{token}', [ProfileController::class, 'processMobileUpload'])
    ->name('mobile.profile.upload.submit');
```

Added authenticated route:

```php
Route::post('/user/profile/generate-qr', [ProfileController::class, 'generateQrCode'])
    ->name('profile.generate-qr');
```

### 5. Mobile Upload Page

**File:** `resources/views/mobile/profile-upload.blade.php`

Features:

- Mobile-optimized responsive design using Tailwind CSS
- Quick camera button for easy photo capture
- File upload with drag-and-drop support
- Live preview of selected photo
- Upload progress bar
- Countdown timer showing token expiration (30 days)
- Success/error message display
- Auto-close on successful upload
- Image validation (JPEG, PNG, JPG, GIF, max 10MB)

### 6. Error Page

**File:** `resources/views/mobile/upload-error.blade.php`

Simple error page for:

- Invalid upload tokens
- Expired upload tokens
- Other upload errors

### 7. Frontend QR Code Modal

**File:** `resources/js/Pages/User/UserProfile.vue`

Enhancements:

- Added `showQrCodeModal` and `qrCodeData` reactive state
- Implemented `openQrCodeModal()` - Fetches QR code from API
- Implemented `closeQrCodeModal()` - Closes modal and clears data
- Implemented `copyQrUrl()` - Copies upload URL to clipboard
- Added QR button to Profile Photo modal with "Upload via Mobile (QR Code)" label
- Added visual divider between QR and file upload options
- Added comprehensive QR Code Modal dialog with:
  - SVG QR code display
  - Copyable upload URL
  - Expiration notice (30 days)
  - Step-by-step upload instructions

## Technical Details

### Token System

- **Token Length:** 64 characters (random string)
- **Default Expiration:** 30 days (43,200 minutes)
- **Uniqueness:** Enforced at database level
- **Validation:** Server-side on every upload request

### File Processing

- **Accepted Formats:** JPEG, PNG, JPG, GIF
- **Max File Size:** 10MB
- **Compression:** Uses existing `processAndCompressImage()` method
  - Resizes to max 400x400 pixels for profile photos
  - Converts to JPEG with 85% quality
  - Stores in `storage/app/public/profile-photos/`

### Security

- QR generation requires authentication
- Upload uses token-based access (no login required)
- Token expiration enforced
- Server-side file validation
- CSRF protection on API calls

### Mobile Experience

- Camera capture support via `capture="environment"` attribute
- Touch-optimized UI
- Quick camera button for one-tap photo capture
- Real-time progress feedback
- Auto-close on success
- Clear error messages

## Usage Flow

### For Desktop Users:

1. Click profile photo to open "Change Profile Photo" modal
2. Click "Upload via Mobile (QR Code)" button
3. System generates QR code and displays in modal
4. User scans QR code with mobile device
5. Upload completes on mobile, profile updates automatically

### For Mobile Users:

1. Scan QR code with mobile camera
2. Browser opens mobile upload page
3. Click "Quick Camera Upload" or choose file
4. Select/take photo
5. Click "Upload Photo"
6. Photo is processed and uploaded
7. Page auto-closes on success

## Files Modified/Created

### Created:

1. `database/migrations/2025_10_29_082724_add_upload_token_to_users_table.php`
2. `resources/views/mobile/profile-upload.blade.php`
3. `resources/views/mobile/upload-error.blade.php`

### Modified:

1. `app/Models/User.php` - Added QR upload methods
2. `app/Http/Controllers/ProfileController.php` - Added QR and mobile upload methods
3. `routes/web.php` - Added mobile upload routes
4. `resources/js/Pages/User/UserProfile.vue` - Added QR modal and functionality

## Testing Checklist

- [ ] Generate QR code from user profile page
- [ ] Verify QR code displays correctly
- [ ] Copy upload URL to clipboard
- [ ] Scan QR code with mobile device
- [ ] Access mobile upload page
- [ ] Upload photo from mobile camera
- [ ] Upload photo from mobile gallery
- [ ] Verify photo appears in profile
- [ ] Test token expiration (manually update database)
- [ ] Test invalid token handling
- [ ] Verify file size validation (>10MB should fail)
- [ ] Verify file type validation (non-images should fail)
- [ ] Test auto-close after successful upload
- [ ] Verify profile photo updates immediately after upload

## Dependencies

- SimpleSoftwareIO/simple-qrcode v4.2.0 (already installed)
- Laravel Storage (public disk)
- PHP GD extension (for image processing)
- PrimeVue Dialog component
- Tailwind CSS
- Font Awesome icons

## Notes

- Token expiration is set to 30 days to allow flexible upload timeframe
- QR code can be regenerated at any time
- Old tokens are not automatically cleaned up (consider adding scheduled task)
- Mobile upload page works offline until upload attempt
- Image compression settings match existing profile photo upload (400x400, 85% quality)
- Implementation follows same pattern as Disbursement and ScholarshipRecord models

## Future Enhancements (Optional)

- Add token cleanup scheduled task to remove expired tokens
- Add upload history/logs
- Add option to customize token expiration time
- Add email notification when photo is uploaded via mobile
- Add multiple photo upload support
- Add photo editing features in mobile view
- Add progress indicator when generating QR code
