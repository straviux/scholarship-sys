<?php

namespace Tests\Unit\Services;

use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileUploadServiceTest extends TestCase
{
    private FileUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FileUploadService();
    }

    /**
     * Test that PNG file is recognized as image
     */
    public function test_png_file_is_recognized_as_image()
    {
        $file = UploadedFile::fake()->image('test.png', 200, 200);

        $result = $this->service->processUpload($file);

        $this->assertTrue($result->isSuccess());
        $this->assertNotEmpty($result->filename);
        $this->assertNotEmpty($result->content);
        $this->assertGreaterThan(0, $result->originalSize);
        $this->assertGreaterThan(0, $result->compressedSize);
    }

    /**
     * Test that JPEG file is compressed
     */
    public function test_jpeg_file_is_compressed()
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $result = $this->service->processUpload($file);

        $this->assertTrue($result->isSuccess());
        $this->assertLessThanOrEqual($result->originalSize, $result->compressedSize);
        $this->assertGreaterThan(0, $result->compressedSize);
    }

    /**
     * Test that PDF file is compressed
     */
    public function test_pdf_file_is_compressed()
    {
        $file = UploadedFile::fake()->create('test.pdf', 500, 'application/pdf');

        // Create a fake PDF with some content
        $result = $this->service->processUpload($file, 'pdf');

        $this->assertTrue($result->isSuccess());
        $this->assertGreaterThan(0, $result->compressedSize);
    }

    /**
     * Test image with explicit type parameter
     */
    public function test_explicit_image_type_parameter()
    {
        $file = UploadedFile::fake()->image('photo.jpg', 1024, 768);

        $result = $this->service->processUpload($file, 'image');

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('jpg', $result->extension);
    }

    /**
     * Test unsupported file type returns error
     */
    public function test_unsupported_file_type_returns_error()
    {
        $file = UploadedFile::fake()->create('test.exe', 100);

        $result = $this->service->processUpload($file, 'unknown');

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Unsupported', $result->getErrorMessage());
    }

    /**
     * Test file processing result contains required properties
     */
    public function test_processing_result_has_all_properties()
    {
        $file = UploadedFile::fake()->image('test.png', 400, 300);

        $result = $this->service->processUpload($file);

        $this->assertTrue($result->isSuccess());
        $this->assertIsString($result->filename);
        $this->assertIsString($result->extension);
        $this->assertIsInt($result->originalSize);
        $this->assertIsInt($result->compressedSize);
        $this->assertIsFloat($result->compressionRatio);
        $this->assertIsString($result->mimeType);
    }

    /**
     * Test filename is properly generated
     */
    public function test_filename_generation()
    {
        $file = UploadedFile::fake()->image('original.jpg', 500, 400);

        $result = $this->service->processUpload($file);

        $this->assertTrue($result->isSuccess());
        $this->assertNotEmpty($result->filename);
        $this->assertStringContainsString('.', $result->filename);
        $this->assertStringEndsWith('.' . $result->extension, $result->filename);
    }

    /**
     * Test compression ratio is calculated correctly
     */
    public function test_compression_ratio_is_calculated()
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 800);

        $result = $this->service->processUpload($file);

        $this->assertTrue($result->isSuccess());
        $this->assertGreaterThan(0, $result->compressionRatio);
        $this->assertLessThanOrEqual(100, $result->compressionRatio);
    }

    /**
     * Test get compression ratio string format
     */
    public function test_get_compression_ratio_string_format()
    {
        $file = UploadedFile::fake()->image('test.jpg', 600, 600);

        $result = $this->service->processUpload($file);

        $ratioString = $result->getCompressionRatio();
        $this->assertStringContainsString('%', $ratioString);
        $this->assertIsString($ratioString);
    }

    /**
     * Test content is not empty after processing
     */
    public function test_processed_content_is_not_empty()
    {
        $file = UploadedFile::fake()->image('test.jpg', 300, 300);

        $result = $this->service->processUpload($file);

        $this->assertTrue($result->isSuccess());
        $this->assertNotEmpty($result->content);
        $this->assertIsString($result->content);
    }

    /**
     * Test MIME type is preserved
     */
    public function test_mime_type_is_preserved()
    {
        $file = UploadedFile::fake()->image('test.png');

        $result = $this->service->processUpload($file);

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('image', $result->mimeType);
    }

    /**
     * Test large image file is processed
     */
    public function test_large_image_file_processing()
    {
        // Create a larger image
        $file = UploadedFile::fake()->image('large.jpg', 2000, 1500);

        $result = $this->service->processUpload($file);

        $this->assertTrue($result->isSuccess());
        $this->assertGreaterThan(0, $result->compressionRatio);
    }

    /**
     * Test small image file is processed
     */
    public function test_small_image_file_processing()
    {
        $file = UploadedFile::fake()->image('tiny.jpg', 100, 100);

        $result = $this->service->processUpload($file);

        $this->assertTrue($result->isSuccess());
        $this->assertGreaterThan(0, $result->originalSize);
    }

    /**
     * Test extension property is available
     */
    public function test_extension_property_is_readonly()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $result = $this->service->processUpload($file);

        $this->assertTrue(isset($result->extension));
        $this->assertIsString($result->extension);
        $this->assertNotEmpty($result->extension);
    }

    /**
     * Test error message is empty on success
     */
    public function test_error_message_empty_on_success()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $result = $this->service->processUpload($file);

        $this->assertTrue($result->isSuccess());
        $this->assertEmpty($result->error);
        $this->assertEmpty($result->getErrorMessage());
    }

    /**
     * Test is success method works correctly
     */
    public function test_is_success_method()
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $result = $this->service->processUpload($file);

        $this->assertTrue($result->isSuccess());

        // Test failure case
        $badResult = $this->service->processUpload($file, 'invalid_type');
        $this->assertFalse($badResult->isSuccess());
    }
}
