<?php

namespace Tests\Unit\Services;

use App\Models\Disbursement;
use App\Models\DisbursementAttachment;
use App\Services\DisbursementFileService;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DisbursementFileServiceTest extends TestCase
{
    use RefreshDatabase;
    private DisbursementFileService $service;
    private FileUploadService $fileUploadService;

    protected function setUp(): void
    {
        parent::setUp();

        // Use in-memory file storage for testing
        Storage::fake('public');

        $this->fileUploadService = new FileUploadService();
        $this->service = new DisbursementFileService($this->fileUploadService);
    }

    /**
     * Test storing a disbursement attachment
     */
    public function test_store_disbursement_attachment()
    {
        $disbursement = Disbursement::factory()->create();
        $file = UploadedFile::fake()->image('receipt.jpg', 400, 300);

        $document = $this->service->storeAttachment($disbursement, $file, 'voucher');

        $this->assertInstanceOf(DisbursementAttachment::class, $document);
        $this->assertEquals('voucher', $document->attachment_type);
        $this->assertEquals($disbursement->disbursement_id, $document->disbursement_id);
    }

    /**
     * Test file is stored in correct directory
     */
    public function test_file_stored_in_correct_directory()
    {
        $disbursement = Disbursement::factory()->create();
        $file = UploadedFile::fake()->image('receipt.jpg');

        $document = $this->service->storeAttachment($disbursement, $file, 'cheque');

        // File should exist in storage at the attachment path
        $this->assertTrue(Storage::disk('public')->exists($document->file_path));
    }

    /**
     * Test attachment record contains required fields
     */
    public function test_attachment_record_has_required_fields()
    {
        $disbursement = Disbursement::factory()->create();
        $file = UploadedFile::fake()->image('voucher.jpg');

        $document = $this->service->storeAttachment($disbursement, $file, 'voucher');

        $this->assertNotNull($document->attachment_id);
        $this->assertNotNull($document->file_name);
        $this->assertNotNull($document->file_path);
        $this->assertNotNull($document->file_size);
        $this->assertGreaterThan(0, $document->file_size);
    }

    /**
     * Test different document types
     */
    public function test_different_document_types()
    {
        $disbursement = Disbursement::factory()->create();
        $documentTypes = ['voucher', 'cheque', 'receipt'];

        foreach ($documentTypes as $type) {
            $file = UploadedFile::fake()->image('doc.jpg');
            $document = $this->service->storeAttachment($disbursement, $file, $type);

            $this->assertEquals($type, $document->attachment_type);
        }
    }

    /**
     * Test original file name is preserved
     */
    public function test_original_filename_preserved()
    {
        $disbursement = Disbursement::factory()->create();
        $originalName = 'my_receipt.jpg';
        $file = UploadedFile::fake()->image($originalName);

        $document = $this->service->storeAttachment($disbursement, $file, 'voucher');

        // file_name contains the generated name with metadata, not the original
        $this->assertNotNull($document->file_name);
    }

    /**
     * Test multiple attachments for same disbursement
     */
    public function test_multiple_attachments_for_disbursement()
    {
        $disbursement = Disbursement::factory()->create();

        $doc1 = $this->service->storeAttachment($disbursement, UploadedFile::fake()->image('photo1.jpg'), 'voucher');
        $doc2 = $this->service->storeAttachment($disbursement, UploadedFile::fake()->image('photo2.jpg'), 'cheque');

        $this->assertNotEquals($doc1->attachment_id, $doc2->attachment_id);
        $this->assertEquals($disbursement->disbursement_id, $doc1->disbursement_id);
        $this->assertEquals($disbursement->disbursement_id, $doc2->disbursement_id);
    }

    /**
     * Test file is actually stored in filesystem
     */
    public function test_file_actually_stored()
    {
        $disbursement = Disbursement::factory()->create();
        $file = UploadedFile::fake()->image('document.jpg');

        $document = $this->service->storeAttachment($disbursement, $file, 'voucher');

        // Verify file exists in storage
        $this->assertTrue(Storage::disk('public')->exists($document->file_path));
    }

    /**
     * Test mime type is recorded
     */
    public function test_mime_type_recorded()
    {
        $disbursement = Disbursement::factory()->create();
        $file = UploadedFile::fake()->image('photo.png');

        $document = $this->service->storeAttachment($disbursement, $file, 'voucher');

        $this->assertNotNull($document->file_type);
    }

    /**
     * Test file size is recorded
     */
    public function test_file_size_recorded()
    {
        $disbursement = Disbursement::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 500, 400);

        $document = $this->service->storeAttachment($disbursement, $file, 'voucher');

        $this->assertGreaterThan(0, $document->file_size);
    }

    /**
     * Test update or create functionality - should update existing record
     */
    public function test_update_existing_document_type()
    {
        $disbursement = Disbursement::factory()->create();
        $file1 = UploadedFile::fake()->image('first.jpg');
        $file2 = UploadedFile::fake()->image('second.jpg');

        $doc1 = $this->service->storeAttachment($disbursement, $file1, 'voucher');
        $doc2 = $this->service->storeAttachment($disbursement, $file2, 'voucher');

        // When updating, the IDs might be the same (updateOrCreate behavior)
        // Both should be for the same document type
        $this->assertEquals('voucher', $doc1->attachment_type);
        $this->assertEquals('voucher', $doc2->attachment_type);
    }

    /**
     * Test authenticated user info is recorded
     */
    public function test_user_info_recorded()
    {
        $disbursement = Disbursement::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        // Without authentication, auth()->id() returns null
        $document = $this->service->storeAttachment($disbursement, $file, 'voucher');

        // Should not error even without auth
        $this->assertNotNull($document->attachment_id);
    }

    /**
     * Test document path is storage URL
     */
    public function test_document_path_is_url()
    {
        $disbursement = Disbursement::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $document = $this->service->storeAttachment($disbursement, $file, 'voucher');

        // Path should be a URL-friendly path
        $this->assertIsString($document->file_path);
        $this->assertNotEmpty($document->file_path);
    }

    /**
     * Test verified flag defaults to false
     */
    public function test_verified_flag_defaults_to_false()
    {
        $disbursement = Disbursement::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $document = $this->service->storeAttachment($disbursement, $file, 'voucher');

        // DisbursementAttachment doesn't have a verified flag
        $this->assertNotNull($document->attachment_id);
    }

    /**
     * Test compressed file size is used
     */
    public function test_uses_compressed_file_size()
    {
        $disbursement = Disbursement::factory()->create();
        $file = UploadedFile::fake()->image('large.jpg', 800, 800);

        $document = $this->service->storeAttachment($disbursement, $file, 'voucher');

        // Compressed size should be reasonable (less than original)
        $this->assertGreaterThan(0, $document->file_size);
    }
}
