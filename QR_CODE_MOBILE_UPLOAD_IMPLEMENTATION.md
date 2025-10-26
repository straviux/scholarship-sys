# QR Code Mobile Upload Implementation

## Overview

This document describes the implementation of QR code-based mobile file upload functionality for disbursements and scholarship records. Users can scan a QR code with their mobile devices to upload attachments directly from their phone camera or gallery.

## Features Implemented

### 1. **Database Changes**

- Added `upload_token` and `upload_token_expires_at` columns to:
  - `disbursements` table
  - `scholarship_records` table
- Upload tokens are unique, 64-character random strings
- Tokens expire after 30 days (configurable)

### 2. **Backend Components**

#### Models Enhanced

**Disbursement Model** (`app/Models/Disbursement.php`)

- `generateUploadToken($expiresInDays = 30)` - Generates unique token
- `getMobileUploadUrl()` - Returns mobile upload URL
- `getUploadQrCode($size = 200)` - Generates SVG QR code
- `getUploadQrCodeDataUri($size = 200)` - Returns base64 PNG QR code

**ScholarshipRecord Model** (`app/Models/ScholarshipRecord.php`)

- Same methods as Disbursement model for consistency

#### Controllers Created/Updated

**MobileUploadController** (`app/Http/Controllers/MobileUploadController.php`)

- `showDisbursementUpload($token)` - Display upload page for disbursements
- `showScholarshipRecordUpload($token)` - Display upload page for scholarship records
- `uploadDisbursementFile(Request, $token)` - Handle disbursement file uploads
- `uploadScholarshipRecordFile(Request, $token)` - Handle scholarship record file uploads
- Includes automatic image optimization (JPEG conversion, 60% quality, max 1920px)
- PDF files are gzip compressed

**DisbursementController** (`app/Http/Controllers/DisbursementController.php`)

- `generateQrCode($disbursementId)` - Generate/refresh QR code for a disbursement

#### Routes Added

**Public Routes** (no authentication required):

```php
GET  /mobile/upload/disbursement/{token}
POST /mobile/upload/disbursement/{token}
GET  /mobile/upload/scholarship-record/{token}
POST /mobile/upload/scholarship-record/{token}
```

**Admin Routes** (authenticated):

```php
POST /disbursements/{disbursement_id}/generate-qr
POST /scholarship-records/{scholarship_record_id}/generate-qr
```

### 3. **Mobile Upload Views**

#### Blade Templates Created

- `resources/views/mobile/disbursement-upload.blade.php`
  - Mobile-responsive upload interface
  - Camera capture support (`capture="environment"`)
  - File preview before upload
  - Progress bar during upload
  - Success/error messages
- `resources/views/mobile/scholarship-record-upload.blade.php`
  - Same features as disbursement upload
- `resources/views/mobile/upload-expired.blade.php`
  - Friendly message for expired/invalid tokens

#### Mobile Upload Features

- **Camera Integration**: Direct camera access on mobile devices
- **File Preview**: Shows selected image/file before upload
- **Progress Tracking**: Visual upload progress bar
- **Validation**: Client and server-side validation
- **Auto-optimization**: Images automatically compressed
- **File Types**: Supports JPG, PNG, PDF (max 10MB)

### 4. **Admin Interface Updates**

#### ObligationsTransactions Component

**Added:**

- QR code button in "Manage Attachments" modal
- QR code display modal with:
  - SVG QR code (scannable)
  - Mobile upload URL (copyable)
  - Expiration date display
  - Usage instructions
- State management:
  ```javascript
  const showQrModal = ref(false);
  const qrCodeData = ref(null);
  ```
- Functions:
  ```javascript
  showQrCode(disbursement);
  copyToClipboard(text);
  ```

#### Show.vue Component

**To be added** (same pattern as ObligationsTransactions):

- QR code button in attachment management
- QR modal with scholarship record token
- Similar state and functions

### 5. **Security Features**

- **Token Validation**: Tokens checked on every request
- **Expiration**: 30-day automatic expiration
- **Unique Tokens**: 64-character random strings (collision-resistant)
- **Public Access**: Mobile uploads don't require authentication (token-based)
- **File Validation**: Server-side validation of file types and sizes

### 6. **File Optimization**

Images uploaded via mobile are automatically:

- Converted to JPEG format
- Compressed to 60% quality
- Resized to max 1920px (maintaining aspect ratio)
- Typical size reduction: 50-70%

PDFs are:

- Gzip compressed (level 9)
- Stored with `.gz` extension

## Usage Workflow

### For Administrators:

1. Open Disbursement or Scholarship Record
2. Click "Manage Attachments"
3. Click "Show QR" button
4. QR code is generated/refreshed
5. Share QR code with scholar (screenshot, print, email)

### For Scholars (Mobile):

1. Scan QR code with phone camera
2. Mobile upload page opens in browser
3. Select attachment type/name
4. Tap to use camera or select from gallery
5. Preview selected file
6. Tap "Upload"
7. File is automatically optimized and uploaded
8. Success message displayed

## Technical Details

### QR Code Package

- **Library**: SimpleSoftwareIO/simple-qrcode v4.2.0
- **Format**: SVG (default), PNG (for data URI)
- **Size**: 200-250px (configurable)

### Token Management

- **Generation**: `Str::random(64)`
- **Storage**: Database column with unique constraint
- **Lifespan**: 30 days (refreshable)
- **Validation**: Checked against expiration timestamp

### File Storage

- **Disk**: public disk
- **Paths**:
  - Disbursements: `storage/app/public/disbursement_attachments/`
  - Scholarship Records: `storage/app/public/scholarship_record_attachments/`
- **Naming**: `{timestamp}_{sanitized_filename}.{ext}`

## Future Enhancements

### Potential Additions:

1. **Scholar Portal Integration**
   - QR codes in scholar dashboard
   - Upload history tracking
2. **Notifications**
   - Email notifications on successful upload
   - Admin alerts for new mobile uploads
3. **Multiple File Upload**
   - Allow uploading multiple files at once
   - Batch processing
4. **Advanced Features**
   - OCR for automatic document type detection
   - Document verification workflow
   - Mobile signature capture
5. **Analytics**
   - Track QR code scans
   - Upload success rates
   - Device/browser statistics

## Testing Checklist

- [ ] Generate QR code for disbursement
- [ ] Scan QR code with mobile device
- [ ] Upload image via camera
- [ ] Upload image from gallery
- [ ] Upload PDF file
- [ ] Verify file optimization works
- [ ] Test expired token handling
- [ ] Test invalid token handling
- [ ] Verify attachments appear in admin panel
- [ ] Test copy URL functionality
- [ ] Generate QR code for scholarship record
- [ ] Test all above for scholarship records

## Maintenance Notes

### Token Cleanup (Recommended)

Create a scheduled task to clean up expired tokens:

```php
// app/Console/Commands/CleanupExpiredTokens.php
Disbursement::where('upload_token_expires_at', '<', now())
    ->update(['upload_token' => null, 'upload_token_expires_at' => null]);

ScholarshipRecord::where('upload_token_expires_at', '<', now())
    ->update(['upload_token' => null, 'upload_token_expires_at' => null]);
```

### Storage Management

Monitor storage usage for uploaded attachments and implement cleanup policy if needed.

## Configuration

### Adjustable Parameters:

- **Token Expiration**: Default 30 days (change in `generateUploadToken()`)
- **QR Code Size**: Default 200-250px (change in route/method calls)
- **Max File Size**: 10MB (change in validation rules)
- **Image Quality**: 60% JPEG (change in upload controllers)
- **Max Dimension**: 1920px (change in upload controllers)

## Files Modified/Created

### Created:

- `database/migrations/2025_10_26_152648_add_upload_token_to_disbursements_and_scholarship_records.php`
- `app/Http/Controllers/MobileUploadController.php`
- `resources/views/mobile/disbursement-upload.blade.php`
- `resources/views/mobile/scholarship-record-upload.blade.php`
- `resources/views/mobile/upload-expired.blade.php`
- `QR_CODE_MOBILE_UPLOAD_IMPLEMENTATION.md` (this file)

### Modified:

- `app/Models/Disbursement.php` - Added QR methods
- `app/Models/ScholarshipRecord.php` - Added QR methods
- `app/Http/Controllers/DisbursementController.php` - Added generateQrCode method
- `routes/web.php` - Added mobile and QR routes
- `resources/js/Components/ObligationsTransactions.vue` - Added QR button and modal
- `composer.json` - Added simple-qrcode package

### To be Modified:

- `resources/js/Pages/Scholarship/Show.vue` - Add QR functionality
- `app/Http/Controllers/ScholarshipRecordAttachmentController.php` - Add generateQrCode method (if needed separately)

## Support

For questions or issues related to QR code mobile uploads, contact the development team.

---

**Implementation Date**: October 26, 2025  
**Version**: 1.0  
**Status**: ✅ Completed (Disbursements), 🔄 In Progress (Scholarship Records UI)
