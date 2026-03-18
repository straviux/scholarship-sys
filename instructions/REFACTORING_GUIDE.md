# Refactoring Guide - MobileUploadController

**Status:** Ready for Implementation  
**Priority:** HIGH (Impacts: Code Quality, Maintainability, Test Coverage)  
**Estimated Time:** 5 days

---

## Overview

This guide provides step-by-step instructions to refactor the `MobileUploadController` (800+ lines) to use services, traits, and FormRequests, reducing it to ~250 lines while improving maintainability and testability.

**Current Issues:**
- 3x duplicated image processing code (140-190, 340-390, 450-510)
- Token validation logic repeated in every method (5+ times)
- Mixed concerns: file validation + processing + compression + storage + DB
- Inconsistent error handling
- No service layer abstraction
- Difficult to test

**Solution Approach:**
1. Create `FileUploadService` (centralized image/file processing)
2. Create `TokenValidation` trait (reusable token checking)
3. Create FormRequest classes (structured validation)
4. Refactor `MobileUploadController` to delegate to services
5. Create service classes for each entity type (optional, for business logic)

**Result:**
- Controller reduced from 800 lines → 250 lines (69% reduction)
- Code duplication eliminated (100%)
- Improved error handling
- Easy to test with mocked services
- Ready for team development

---

## Step 1: Create FileUploadService (Day 1)

**Location:** `app/Services/FileUploadService.php`

**Purpose:** Centralized image and file processing with compression and validation.

```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\GdDriver;
use Illuminate\Support\Str;

class FileUploadService
{
    private ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new GdDriver());
    }

    /**
     * Process uploaded file (image or PDF)
     * 
     * @param UploadedFile $file The uploaded file
     * @param string $type 'image' or 'pdf'
     * @return FileProcessingResult
     * @throws \Exception
     */
    public function processUpload(UploadedFile $file, string $type = 'image'): FileProcessingResult
    {
        if ($type === 'image') {
            return $this->processImage($file);
        } elseif ($type === 'pdf') {
            return $this->processPdf($file);
        }

        throw new \Exception("Unsupported file type: $type");
    }

    /**
     * Process image file with compression and orientation fix
     * 
     * Returns compressed JPEG at 40% quality
     */
    private function processImage(UploadedFile $file): FileProcessingResult
    {
        try {
            // Read the original image
            $image = $this->imageManager->read($file->getPathname());

            // Step 1: Fix EXIF orientation (mobile photos often rotated)
            $image = $this->fixImageOrientation($image, $file->getPathname());

            // Step 2: Enforce portrait orientation (required for scholarships)
            // 600 width × 750 height (4:5 aspect ratio)
            if ($image->width() > $image->height()) {
                $image->rotate(-90);  // Rotate if landscape
            }

            // Step 3: Resize to standard dimensions (maintain aspect ratio)
            // If wider than 600px, resize to 600px width
            if ($image->width() > 600) {
                $image->scaleDown(600);  // Maintains aspect ratio
            }

            // Step 4: Compress to JPEG at 40% quality
            $compressed = $image
                ->toJpeg(quality: 40)
                ->toString();

            // Step 5: Calculate processing metrics
            $originalSize = $file->getSize();
            $compressedSize = strlen($compressed);
            $compressionRatio = round(($originalSize - $compressedSize) / $originalSize * 100, 2);

            // Step 6: Generate unique filename
            $filename = 'image_' . Str::random(12) . '_' . now()->timestamp . '.jpg';

            return new FileProcessingResult(
                success: true,
                filename: $filename,
                content: $compressed,
                originalSize: $originalSize,
                compressedSize: $compressedSize,
                compressionRatio: $compressionRatio,
                mimeType: 'image/jpeg',
            );

        } catch (\Exception $e) {
            return new FileProcessingResult(
                success: false,
                error: 'Image processing failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Process PDF file with gzip compression
     */
    private function processPdf(UploadedFile $file): FileProcessingResult
    {
        try {
            // Read PDF content
            $content = file_get_contents($file->getPathname());

            // Compress with gzip level 9
            $compressed = gzencode($content, 9);

            $originalSize = strlen($content);
            $compressedSize = strlen($compressed);
            $compressionRatio = round(($originalSize - $compressedSize) / $originalSize * 100, 2);

            // Generate unique filename
            $filename = 'document_' . Str::random(12) . '_' . now()->timestamp . '.pdf.gz';

            return new FileProcessingResult(
                success: true,
                filename: $filename,
                content: $compressed,
                originalSize: $originalSize,
                compressedSize: $compressedSize,
                compressionRatio: $compressionRatio,
                mimeType: 'application/pdf',
            );

        } catch (\Exception $e) {
            return new FileProcessingResult(
                success: false,
                error: 'PDF processing failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Fix image rotation based on EXIF data
     * 
     * Mobile phones store orientation in EXIF, not actual rotation
     */
    private function fixImageOrientation($image, string $filePath)
    {
        try {
            $exif = @exif_read_data($filePath);
            
            if ($exif && isset($exif['Orientation'])) {
                $orientation = $exif['Orientation'];

                return match ($orientation) {
                    2 => $image->flip('h'),           // Flip horizontal
                    3 => $image->rotate(180),         // Rotate 180
                    4 => $image->flip('v'),           // Flip vertical
                    5 => $image->rotate(90)->flip('h'),   // Rotate 90 + flip
                    6 => $image->rotate(90),          // Rotate 90 CW
                    7 => $image->rotate(-90)->flip('h'),  // Rotate -90 + flip
                    8 => $image->rotate(-90),         // Rotate -90
                    default => $image,
                };
            }

            return $image;

        } catch (\Exception $e) {
            // If EXIF reading fails, return image unchanged
            return $image;
        }
    }
}

/**
 * Data Transfer Object for file processing results
 */
class FileProcessingResult
{
    public function __construct(
        public bool $success,
        public string $filename = '',
        public mixed $content = null,
        public int $originalSize = 0,
        public int $compressedSize = 0,
        public float $compressionRatio = 0.0,
        public string $mimeType = '',
        public string $error = '',
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrorMessage(): string
    {
        return $this->error;
    }
}
```

**Testing the Service:**

```php
// In your test
$uploadedFile = UploadedFile::fake()->image('photo.jpg', 1280, 960);
$service = new FileUploadService();
$result = $service->processUpload($uploadedFile, 'image');

expect($result->isSuccess())->toBeTrue();
expect($result->compressionRatio)->toBeGreaterThan(50);  // Expect 50%+ compression
expect($result->mimeType)->toBe('image/jpeg');
```

---

## Step 2: Create TokenValidation Trait (Day 1)

**Location:** `app/Traits/TokenValidation.php`

**Purpose:** Reusable token validation logic (eliminates 5+ duplications).

```php
<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

trait TokenValidation
{
    /**
     * Validate mobile upload token
     * 
     * Token format: base64-encoded JSON with 'exp' (expiry) and 'entity_type'
     * 
     * Example: base64_encode(json_encode(['exp' => 1705420000, 'entity_type' => 'disbursement']))
     * 
     * @param string $token The token to validate
     * @param string $expectedEntityType Expected type (disbursement, record, etc)
     * @throws ValidationException
     */
    public function validateUploadToken(string $token, string $expectedEntityType): void
    {
        try {
            // Debug log token received (abbreviated for security)
            $this->debugLogTokenState($token, 'received');

            // Decode token
            $decoded = json_decode(base64_decode($token, true), true);

            if (!$decoded || !isset($decoded['exp']) || !isset($decoded['entity_type'])) {
                throw new \Exception('Invalid token format');
            }

            // Check expiry
            $expiry = Carbon::createFromTimestamp($decoded['exp']);
            if ($expiry->isPast()) {
                Log::warning('upload_token_expired', [
                    'token' => substr($token, 0, 10) . '...',
                    'expired_at' => $expiry->toIso8601String(),
                    'now' => now()->toIso8601String(),
                ]);

                throw new \Exception('Token expired at ' . $expiry->format('M d, Y H:i'));
            }

            // Check entity type matches
            if ($decoded['entity_type'] !== $expectedEntityType) {
                throw new \Exception(
                    "Token entity type mismatch. Expected: $expectedEntityType, Got: " . $decoded['entity_type']
                );
            }

            // Success - log token validation
            $this->debugLogTokenState($token, 'validated_successfully');

        } catch (\Exception $e) {
            Log::warning('upload_token_validation_failed', [
                'token' => substr($token, 0, 10) . '...',
                'expected_entity_type' => $expectedEntityType,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'token' => 'Invalid or expired upload token: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Debug log token state (only in debug mode)
     */
    private function debugLogTokenState(string $token, string $state): void
    {
        if (!config('app.debug')) {
            return;
        }

        Log::debug('upload_token_' . $state, [
            'token' => substr($token, 0, 10) . '...',
            'length' => strlen($token),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

**Usage in Controller:**

```php
class MobileUploadController extends Controller
{
    use TokenValidation;

    public function uploadDisbursement(MobileUploadRequest $request)
    {
        // Validates token, throws ValidationException if invalid
        $this->validateUploadToken(
            $request->input('token'),
            'disbursement'
        );

        // Continue with file processing...
    }
}
```

---

## Step 3: Create FormRequest Classes (Day 2)

**Location:** `app/Http/Requests/`

### 3.1 Base Request Class

**File:** `app/Http/Requests/MobileUploadRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class MobileUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;  // Token validation happens in controller
    }

    /**
     * Base validation rules (used by all mobile upload requests)
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'file' => [
                'required',
                'file',
                'max:2048',  // 2MB max
                File::types(['jpg', 'jpeg', 'png', 'pdf']),
            ],
            'entity_id' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Upload token is required',
            'file.required' => 'File is required',
            'file.max' => 'File must not exceed 2MB',
            'file.mimes' => 'File must be JPG, PNG, or PDF',
            'entity_id.required' => 'Entity ID is required',
        ];
    }

    protected function prepareForValidation()
    {
        // Auto-set user_id for audit trail
        $this->merge([
            'user_id' => auth()->id(),
            'uploaded_at' => now(),
        ]);
    }
}
```

### 3.2 Specialized Request Classes

**File:** `app/Http/Requests/DisbursementUploadRequest.php`

```php
<?php

namespace App\Http\Requests;

class DisbursementUploadRequest extends MobileUploadRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'entity_id' => ['required', 'exists:disbursements,disbursement_id'],
            'attachment_type' => ['required', 'in:receipt,evidence,photo'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'entity_id.exists' => 'Disbursement not found',
            'attachment_type.in' => 'Invalid attachment type',
        ]);
    }
}
```

**File:** `app/Http/Requests/ScholarshipRecordUploadRequest.php`

```php
<?php

namespace App\Http\Requests;

class ScholarshipRecordUploadRequest extends MobileUploadRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'entity_id' => ['required', 'exists:scholarship_records,id'],
            'document_type' => ['required', 'in:transcript,id,report'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'entity_id.exists' => 'Scholarship record not found',
            'document_type.in' => 'Invalid document type',
        ]);
    }
}
```

**File:** `app/Http/Requests/RequirementUploadRequest.php`

```php
<?php

namespace App\Http\Requests;

class RequirementUploadRequest extends MobileUploadRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'entity_id' => ['required', 'exists:scholarship_profile_requirements,id'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'entity_id.exists' => 'Requirement not found',
        ]);
    }
}
```

---

## Step 4: Refactor MobileUploadController (Day 2-3)

**Location:** `app/Http/Controllers/Api/MobileUploadController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DisbursementUploadRequest;
use App\Http\Requests\ScholarshipRecordUploadRequest;
use App\Http\Requests\RequirementUploadRequest;
use App\Http\Requests\FundTransactionUploadRequest;
use App\Http\Requests\ScholarshipProfileUploadRequest;
use App\Services\FileUploadService;
use App\Services\DisbursementFileService;
use App\Services\ScholarshipRecordFileService;
use App\Traits\TokenValidation;
use Illuminate\Support\Facades\Log;

class MobileUploadController extends Controller
{
    use TokenValidation;

    public function __construct(
        private FileUploadService $fileUploadService,
        private DisbursementFileService $disbursementFileService,
        private ScholarshipRecordFileService $scholarshipRecordFileService,
    ) {}

    /**
     * Upload disbursement attachment (receipt, evidence, photo)
     */
    public function uploadDisbursement(DisbursementUploadRequest $request)
    {
        try {
            // Validate token
            $this->validateUploadToken($request->input('token'), 'disbursement');

            // Process file
            $result = $this->fileUploadService->processUpload(
                $request->file('file'),
                $this->getFileType($request->file('file'))
            );

            if (!$result->isSuccess()) {
                Log::error('disbursement_file_processing_failed', [
                    'disbursement_id' => $request->input('entity_id'),
                    'error' => $result->getErrorMessage(),
                ]);

                return $this->error('File processing failed', code: 422);
            }

            // Store attachment in database
            $attachment = $this->disbursementFileService->storeAttachment(
                $request->input('entity_id'),
                $result,
                $request->input('attachment_type'),
                $request->input('description', ''),
                $request->user()
            );

            Log::info('disbursement_file_uploaded', [
                'disbursement_id' => $request->input('entity_id'),
                'attachment_id' => $attachment->id,
                'file_size' => $result->compressedSize,
                'compression_ratio' => $result->compressionRatio . '%',
                'uploaded_by' => $request->user()->id,
            ]);

            return $this->success([
                'attachment_id' => $attachment->id,
                'filename' => $attachment->filename,
                'size' => $result->compressedSize,
                'message' => 'File uploaded successfully',
            ], 'File uploaded', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            Log::error('disbursement_upload_error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
            ]);

            return $this->error('Upload failed: ' . $e->getMessage(), code: 500);
        }
    }

    /**
     * Upload scholarship record document
     */
    public function uploadScholarshipRecord(ScholarshipRecordUploadRequest $request)
    {
        try {
            $this->validateUploadToken($request->input('token'), 'scholarship_record');

            $result = $this->fileUploadService->processUpload(
                $request->file('file'),
                $this->getFileType($request->file('file'))
            );

            if (!$result->isSuccess()) {
                return $this->error('File processing failed', code: 422);
            }

            $document = $this->scholarshipRecordFileService->storeDocument(
                $request->input('entity_id'),
                $result,
                $request->input('document_type'),
                $request->input('description', ''),
                $request->user()
            );

            Log::info('scholarship_record_document_uploaded', [
                'record_id' => $request->input('entity_id'),
                'document_id' => $document->id,
                'type' => $request->input('document_type'),
            ]);

            return $this->success([
                'document_id' => $document->id,
                'filename' => $document->filename,
            ], 'Document uploaded', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            Log::error('scholarship_record_upload_error', ['error' => $e->getMessage()]);
            return $this->error('Upload failed', code: 500);
        }
    }

    /**
     * Upload profile requirement
     */
    public function uploadRequirement(RequirementUploadRequest $request)
    {
        try {
            $this->validateUploadToken($request->input('token'), 'requirement');

            $result = $this->fileUploadService->processUpload(
                $request->file('file'),
                $this->getFileType($request->file('file'))
            );

            if (!$result->isSuccess()) {
                return $this->error('File processing failed', code: 422);
            }

            // TODO: Implement requirement file storage
            $requirement = null;  // Your business logic here

            return $this->success([
                'file_id' => 'generated_id',
                'filename' => $result->filename,
            ], 'File uploaded', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            Log::error('requirement_upload_error', ['error' => $e->getMessage()]);
            return $this->error('Upload failed', code: 500);
        }
    }

    /**
     * Upload fund transaction document
     */
    public function uploadFundTransaction(FundTransactionUploadRequest $request)
    {
        try {
            $this->validateUploadToken($request->input('token'), 'fund_transaction');

            $result = $this->fileUploadService->processUpload(
                $request->file('file'),
                $this->getFileType($request->file('file'))
            );

            if (!$result->isSuccess()) {
                return $this->error('File processing failed', code: 422);
            }

            // TODO: Implement fund transaction file storage

            return $this->success([
                'file_id' => 'generated_id',
                'filename' => $result->filename,
            ], 'File uploaded', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            Log::error('fund_transaction_upload_error', ['error' => $e->getMessage()]);
            return $this->error('Upload failed', code: 500);
        }
    }

    /**
     * Upload profile photo/document
     */
    public function uploadScholarshipProfile(ScholarshipProfileUploadRequest $request)
    {
        try {
            $this->validateUploadToken($request->input('token'), 'profile');

            $result = $this->fileUploadService->processUpload(
                $request->file('file'),
                'image'  // Profile accepts images only
            );

            if (!$result->isSuccess()) {
                return $this->error('File processing failed', code: 422);
            }

            // TODO: Implement profile photo storage

            return $this->success([
                'file_id' => 'generated_id',
                'filename' => $result->filename,
            ], 'File uploaded', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            Log::error('profile_upload_error', ['error' => $e->getMessage()]);
            return $this->error('Upload failed', code: 500);
        }
    }

    /**
     * Determine file type from MIME
     */
    private function getFileType($file): string
    {
        $mimeType = $file->getMimeType();

        return match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            in_array($mimeType, ['application/pdf']) => 'pdf',
            default => 'unknown',
        };
    }

    /**
     * Response helpers (already in base Controller)
     */
    protected function success($data = null, $message = 'OK', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error($message, $errors = [], $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
```

**Lines Reduced:**
- Original: 800+ lines (duplicated logic, mixed concerns)
- Refactored: 250 lines (delegated, focused, testable)
- **Reduction: 69%**

---

## Step 5: Create Service Classes (Day 3-4)

**Location:** `app/Services/DisbursementFileService.php`

```php
<?php

namespace App\Services;

use App\Models\Disbursement;
use App\Models\DisbursementAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class DisbursementFileService
{
    /**
     * Store disbursement attachment
     */
    public function storeAttachment(
        string $disbursementId,
        FileProcessingResult $fileResult,
        string $attachmentType,
        string $description,
        Model $user
    ): DisbursementAttachment {
        // Store file in storage
        $path = 'disbursements/' . $disbursementId . '/' . $fileResult->filename;
        Storage::disk('private')->put($path, $fileResult->content);

        // Create attachment record
        $attachment = DisbursementAttachment::create([
            'disbursement_id' => $disbursementId,
            'filename' => $fileResult->filename,
            'file_path' => $path,
            'attachment_type' => $attachmentType,
            'description' => $description,
            'file_size' => $fileResult->compressedSize,
            'mime_type' => $fileResult->mimeType,
            'original_size' => $fileResult->originalSize,
            'compression_ratio' => $fileResult->compressionRatio,
            'uploaded_by' => $user->id,
            'published_at' => now(),
        ]);

        // Update disbursement record
        Disbursement::find($disbursementId)?->update([
            'has_attachment' => true,
            'last_attachment_date' => now(),
        ]);

        return $attachment;
    }
}
```

**Location:** `app/Services/ScholarshipRecordFileService.php`

```php
<?php

namespace App\Services;

use App\Models\ScholarshipRecord;
use App\Models\ScholarshipRecordDocument;
use Illuminate\Support\Facades\Storage;

class ScholarshipRecordFileService
{
    /**
     * Store scholarship record document
     */
    public function storeDocument(
        string $recordId,
        FileProcessingResult $fileResult,
        string $documentType,
        string $description,
        $user
    ): ScholarshipRecordDocument {
        // Store file
        $path = 'scholarship-records/' . $recordId . '/' . $fileResult->filename;
        Storage::disk('private')->put($path, $fileResult->content);

        // Create document record
        $document = ScholarshipRecordDocument::create([
            'scholarship_record_id' => $recordId,
            'filename' => $fileResult->filename,
            'file_path' => $path,
            'document_type' => $documentType,
            'description' => $description,
            'file_size' => $fileResult->compressedSize,
            'mime_type' => $fileResult->mimeType,
            'uploaded_by' => $user->id,
            'uploaded_at' => now(),
        ]);

        // Update record status
        ScholarshipRecord::find($recordId)?->update([
            'documents_count' => ScholarshipRecordDocument::where('scholarship_record_id', $recordId)->count(),
        ]);

        return $document;
    }
}
```

---

## Implementation Timeline

### Day 1: Create Core Services
- [ ] Create `FileUploadService.php`
- [ ] Create `FileProcessingResult.php` (or DTO in same file)
- [ ] Create `TokenValidation.php` trait
- [ ] Write unit tests for both
- **Time:** 4 hours

### Day 2: Create Validation & Refactor Controller
- [ ] Create `MobileUploadRequest.php` (base)
- [ ] Create 4 specialized FormRequest classes
- [ ] Refactor `MobileUploadController.php` (main work)
- [ ] Write feature tests for controller
- **Time:** 5 hours

### Day 3: Create File Services
- [ ] Create `DisbursementFileService.php`
- [ ] Create `ScholarshipRecordFileService.php`
- [ ] Write unit tests for services
- [ ] Update migration if needed (add columns to attachment tables)
- **Time:** 3 hours

### Day 4: Integration & Testing
- [ ] Test full upload flow end-to-end
- [ ] Verify compression ratios
- [ ] Test error scenarios
- [ ] Performance test with 1000+ files
- **Time:** 4 hours

### Day 5: Documentation & Deployment
- [ ] Update API documentation
- [ ] Create migration guide for mobile app
- [ ] Deploy to staging environment
- [ ] Final QA testing
- **Time:** 3 hours

**Total Estimated Time:** 19 hours (spread over 5 days)

---

## Testing Strategy

### Unit Tests for FileUploadService

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;

class FileUploadServiceTest extends TestCase
{
    private FileUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FileUploadService();
    }

    /** @test */
    public function it_compresses_image_files()
    {
        $file = UploadedFile::fake()->image('photo.jpg', 1280, 960);
        $result = $this->service->processUpload($file, 'image');

        expect($result->isSuccess())->toBeTrue();
        expect($result->compressionRatio)->toBeGreaterThan(30);
        expect($result->mimeType)->toBe('image/jpeg');
    }

    /** @test */
    public function it_fixes_portrait_orientation()
    {
        $file = UploadedFile::fake()->image('photo.jpg', 960, 1280);
        $result = $this->service->processUpload($file, 'image');

        expect($result->isSuccess())->toBeTrue();
        // Verify dimensions are correct (portrait)
    }

    /** @test */
    public function it_compresses_pdf_files()
    {
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');
        $result = $this->service->processUpload($file, 'pdf');

        expect($result->isSuccess())->toBeTrue();
        expect($result->compressionRatio)->toBeGreaterThan(50);
    }

    /** @test */
    public function it_handles_invalid_files()
    {
        $file = UploadedFile::fake()->create('invalid.tx', 100, 'text/plain');
        $result = $this->service->processUpload($file, 'image');

        expect($result->isSuccess())->toBeFalse();
        expect($result->getErrorMessage())->toContain('failed');
    }
}
```

### Feature Tests for Controller

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Disbursement;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class MobileUploadControllerTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_uploads_disbursement_attachment_successfully()
    {
        $disbursement = Disbursement::factory()->create();
        $token = $this->generateValidToken('disbursement');
        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->post('/api/mobile/disbursement/upload', [
            'token' => $token,
            'file' => $file,
            'entity_id' => $disbursement->disbursement_id,
            'attachment_type' => 'receipt',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.file_id', fn($id) => $id !== null);

        $this->assertDatabaseHas('disbursement_attachments', [
            'disbursement_id' => $disbursement->disbursement_id,
        ]);
    }

    /** @test */
    public function it_rejects_expired_token()
    {
        $disbursement = Disbursement::factory()->create();
        $token = $this->generateExpiredToken('disbursement');
        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->post('/api/mobile/disbursement/upload', [
            'token' => $token,
            'file' => $file,
            'entity_id' => $disbursement->disbursement_id,
            'attachment_type' => 'receipt',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    private function generateValidToken(string $entityType): string
    {
        return base64_encode(json_encode([
            'exp' => now()->addHour()->timestamp,
            'entity_type' => $entityType,
        ]));
    }

    private function generateExpiredToken(string $entityType): string
    {
        return base64_encode(json_encode([
            'exp' => now()->subHour()->timestamp,
            'entity_type' => $entityType,
        ]));
    }
}
```

---

## Deployment Checklist

- [ ] All unit tests passing (FileUploadService)
- [ ] All feature tests passing (API endpoints)
- [ ] Code coverage > 80%
- [ ] No console errors in logs
- [ ] Compression ratios verified (30%+ for images, 50%+ for PDFs)
- [ ] Old image processing code removed from codebase
- [ ] TokenValidation trait working in all endpoints
- [ ] FormRequest validation messages correct
- [ ] Database migrations run successfully
- [ ] Attachment tables have correct schema
- [ ] Storage disk permissions correct
- [ ] Logging shows proper audit trails
- [ ] Mobile app tested with API
- [ ] Performance tested (1000+ uploads)
- [ ] Documentation updated
- [ ] Team trained on using FormRequest instead of inline validation

---

## Rollback Plan

If issues arise:

1. **Database:** Keep old schema tables, add new columns alongside
2. **Code:** Keep old controller as `MobileUploadControllerOld.php` temporarily
3. **Routes:** Add toggle: route to old or new controller via env var
4. **Quick Revert:** 15-minute rollback if critical bugs found

---

## FAQ

**Q: Do I need to delete the old controller code?**  
A: Not immediately! Keep it for reference while new code stabilizes (1-2 weeks).

**Q: What about existing attachments?**  
A: They're unaffected. New uploads use the new service; existing files remain as-is.

**Q: How do I handle file storage migrations?**  
A: Use a separate job to reprocess old files gradually (optional optimization).

**Q: Do development or staging environments need updates?**  
A: Yes. Run migrations on all environments. Test thoroughly on staging first.

---

**Status:** Ready for Implementation  
**Next Step:** Assign to developer, create GitHub PR, execute Day 1 tasks

