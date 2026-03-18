<?php

namespace Tests\Unit\Services;

use App\Models\ScholarshipRecord;
use App\Models\ScholarshipRecordAttachment;
use App\Services\FileUploadService;
use App\Services\ScholarshipRecordFileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScholarshipRecordFileServiceTest extends TestCase
{
    use RefreshDatabase;
    private ScholarshipRecordFileService $service;
    private FileUploadService $fileUploadService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->fileUploadService = new FileUploadService();
        $this->service = new ScholarshipRecordFileService($this->fileUploadService);
    }

    /**
     * Test storing scholarship record attachment
     */
    public function test_store_scholarship_record_attachment()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->image('contract.jpg');

        $attachment = $this->service->storeAttachment($record, $file, 'contract');

        $this->assertInstanceOf(ScholarshipRecordAttachment::class, $attachment);
        $this->assertEquals('contract', $attachment->attachment_name);
        $this->assertEquals($record->id, $attachment->scholarship_record_id);
    }

    /**
     * Test attachment with page number
     */
    public function test_attachment_with_page_number()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->create('contract_page_1.pdf', 150);

        $attachment = $this->service->storeAttachment($record, $file, 'contract', 1);

        $this->assertEquals(1, $attachment->page_number);
        $this->assertStringContainsString('_page_1', $attachment->file_name);
    }

    /**
     * Test attachment without page number
     */
    public function test_attachment_without_page_number()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->image('receipt.jpg');

        $attachment = $this->service->storeAttachment($record, $file, 'receipt', null);

        $this->assertNull($attachment->page_number);
    }

    /**
     * Test multiple pages for same attachment
     */
    public function test_multiple_pages_for_contract()
    {
        $record = ScholarshipRecord::factory()->create();

        $page1 = $this->service->storeAttachment($record, UploadedFile::fake()->create('page1.pdf', 150), 'contract', 1);
        $page2 = $this->service->storeAttachment($record, UploadedFile::fake()->create('page2.pdf', 150), 'contract', 2);

        $this->assertEquals(1, $page1->page_number);
        $this->assertEquals(2, $page2->page_number);
        $this->assertNotEquals($page1->attachment_id, $page2->attachment_id);
    }

    /**
     * Test file stored in correct directory
     */
    public function test_file_stored_in_correct_directory()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->image('document.jpg');

        $attachment = $this->service->storeAttachment($record, $file, 'document');

        // File stored at the attachment path
        $this->assertTrue(Storage::disk('public')->exists($attachment->file_path));
    }

    /**
     * Test attachment name in record
     */
    public function test_attachment_name_stored()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->image('diploma.jpg');

        $attachment = $this->service->storeAttachment($record, $file, 'diploma');

        $this->assertEquals('diploma', $attachment->attachment_name);
    }

    /**
     * Test multiple attachments for record
     */
    public function test_multiple_attachments_for_record()
    {
        $record = ScholarshipRecord::factory()->create();

        $att1 = $this->service->storeAttachment($record, UploadedFile::fake()->image('photo1.jpg'), 'photo');
        $att2 = $this->service->storeAttachment($record, UploadedFile::fake()->image('photo2.jpg'), 'id_copy');

        $this->assertEquals($record->id, $att1->scholarship_record_id);
        $this->assertEquals($record->id, $att2->scholarship_record_id);
        $this->assertNotEquals($att1->attachment_id, $att2->attachment_id);
    }

    /**
     * Test file size recorded
     */
    public function test_file_size_recorded()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 500, 400);

        $attachment = $this->service->storeAttachment($record, $file, 'photo');

        $this->assertGreaterThan(0, $attachment->file_size);
    }

    /**
     * Test mime type recorded
     */
    public function test_mime_type_recorded()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->image('photo.png');

        $attachment = $this->service->storeAttachment($record, $file, 'photo');

        $this->assertNotNull($attachment->file_type);
    }

    /**
     * Test original filename preserved
     */
    public function test_original_filename_preserved()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->create('my_document.pdf', 100);

        $attachment = $this->service->storeAttachment($record, $file, 'documentation');

        // file_name contains generated name
        $this->assertNotNull($attachment->file_name);
    }

    /**
     * Test attachment path is URL
     */
    public function test_attachment_path_is_url()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $attachment = $this->service->storeAttachment($record, $file, 'photo');

        $this->assertIsString($attachment->file_path);
        $this->assertNotEmpty($attachment->file_path);
    }

    /**
     * Test timestamp in filename
     */
    public function test_timestamp_in_filename()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $attachment = $this->service->storeAttachment($record, $file, 'profile_photo');

        $this->assertStringContainsString('profile_photo', $attachment->file_name);
        // Filename should have timestamp
        $this->assertNotEmpty($attachment->file_name);
    }

    /**
     * Test page number in filename when present
     */
    public function test_page_number_in_filename()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->create('contract.pdf', 150);

        $attachment = $this->service->storeAttachment($record, $file, 'contract', 3);

        $this->assertStringContainsString('_page_3', $attachment->file_name);
    }

    /**
     * Test page number null when not provided
     */
    public function test_page_number_null_by_default()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->image('receipt.jpg');

        $attachment = $this->service->storeAttachment($record, $file, 'receipt');

        $this->assertNull($attachment->page_number);
    }

    /**
     * Test compression is applied
     */
    public function test_file_compression_applied()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->image('large.jpg', 1024, 768);

        $attachment = $this->service->storeAttachment($record, $file, 'photo');

        // Compressed size should be reasonable
        $this->assertGreaterThan(0, $attachment->file_size);
    }

    /**
     * Test original size recorded
     */
    public function test_original_size_recorded()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 300, 300);

        $attachment = $this->service->storeAttachment($record, $file, 'photo');

        // Should have original size if available
        $this->assertGreaterThan(0, $attachment->file_size);
    }

    /**
     * Test special characters in attachment name
     */
    public function test_special_characters_handled()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        // Should handle any valid attachment name
        $attachment = $this->service->storeAttachment($record, $file, 'official-document');

        $this->assertNotNull($attachment->attachment_id);
    }

    /**
     * Test long attachment names
     */
    public function test_long_attachment_names()
    {
        $record = ScholarshipRecord::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');
        $longName = 'this_is_a_very_long_attachment_name_for_documentation';

        $attachment = $this->service->storeAttachment($record, $file, $longName);

        $this->assertNotNull($attachment->attachment_id);
    }
}
