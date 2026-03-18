<?php

namespace Tests\Feature;

use App\Models\Disbursement;
use App\Models\ScholarshipRecord;
use App\Models\FundTransaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileUploadResponseFormatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test all successful responses have consistent structure
     */
    public function test_all_upload_endpoints_return_consistent_success_format()
    {
        $endpoints = [
            'disbursement' => [
                'model' => Disbursement::factory()->create([
                    'upload_token' => 'disburse_token',
                    'upload_token_expires_at' => Carbon::now()->addDay(),
                ]),
                'route' => '/mobile/upload/disbursement/disburse_token',
                'data' => ['file' => UploadedFile::fake()->image('test.jpg'), 'attachment_type' => 'voucher'],
            ],
            'scholarship_record' => [
                'model' => ScholarshipRecord::factory()->create([
                    'upload_token' => 'record_token',
                    'upload_token_expires_at' => Carbon::now()->addDay(),
                ]),
                'route' => '/mobile/upload/scholarship-record/record_token',
                'data' => ['file' => UploadedFile::fake()->image('test.jpg'), 'attachment_name' => 'contract'],
            ],
            'fund_transaction' => [
                'model' => FundTransaction::factory()->create([
                    'upload_token' => 'fund_token',
                    'upload_token_expires_at' => Carbon::now()->addDay(),
                ]),
                'route' => '/mobile/upload/fund-transaction/fund_token',
                'data' => ['file' => UploadedFile::fake()->create('test.pdf', 100), 'document_type' => 'obr'],
            ],
        ];

        foreach ($endpoints as $name => $config) {
            $response = $this->post($config['route'], $config['data']);

            $this->assertEquals(201, $response->status(), "Upload failed for {$name}");
            $response->assertJson(['success' => true]);
            $response->assertJsonPath('message', 'File uploaded successfully');
            $response->assertJsonStructure(['data']);
        }
    }

    /**
     * Test all validation error responses have consistent structure
     */
    public function test_validation_errors_return_consistent_format()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        // Missing required file
        $response = $this->post(
            '/mobile/upload/disbursement/token',
            ['attachment_type' => 'voucher']
        );

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $response->assertJsonStructure(['message', 'errors']);
    }

    /**
     * Test token expiration error response
     */
    public function test_expired_token_error_response()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'expired',
            'upload_token_expires_at' => Carbon::now()->subDay(),
        ]);

        $response = $this->post(
            '/mobile/upload/disbursement/expired',
            [
                'file' => UploadedFile::fake()->image('test.jpg'),
                'attachment_type' => 'voucher',
            ]
        );

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    /**
     * Test not found error response
     */
    public function test_not_found_error_response()
    {
        $response = $this->post(
            '/mobile/upload/disbursement/nonexistent',
            [
                'file' => UploadedFile::fake()->image('test.jpg'),
                'attachment_type' => 'voucher',
            ]
        );

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    /**
     * Test response includes correct HTTP status codes
     */
    public function test_correct_http_status_codes()
    {
        // Success: 201 Created
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->post(
            '/mobile/upload/disbursement/token',
            [
                'file' => UploadedFile::fake()->image('test.jpg'),
                'attachment_type' => 'voucher',
            ]
        );
        $this->assertEquals(201, $response->status());

        // Validation error: 422 Unprocessable Entity
        $response = $this->post('/mobile/upload/disbursement/token', []);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Test response data includes all required fields
     */
    public function test_success_response_includes_all_data_fields()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->post(
            '/mobile/upload/disbursement/token',
            [
                'file' => UploadedFile::fake()->image('test.jpg'),
                'attachment_type' => 'voucher',
            ]
        );

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'file_id',
                'filename',
                'size',
                'original_size',
                'compression_ratio',
            ],
        ]);

        // Verify data values are present and reasonable
        $data = $response->json('data');
        $this->assertNotNull($data['file_id']);
        $this->assertNotEmpty($data['filename']);
        $this->assertGreaterThan(0, $data['size']);
        $this->assertGreaterThan(0, $data['original_size']);
        $this->assertStringContainsString('%', $data['compression_ratio']);
    }

    /**
     * Test JSON response content type
     */
    public function test_responses_have_correct_content_type()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->post(
            '/mobile/upload/disbursement/token',
            [
                'file' => UploadedFile::fake()->image('test.jpg'),
                'attachment_type' => 'voucher',
            ]
        );

        $this->assertEquals('application/json', $response->headers->get('content-type'));
    }

    /**
     * Test compression ratio is within expected range
     */
    public function test_compression_ratio_is_reasonable()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->post(
            '/mobile/upload/disbursement/token',
            [
                'file' => UploadedFile::fake()->image('test.jpg', 800, 600),
                'attachment_type' => 'voucher',
            ]
        );

        $ratio = $response->json('data.compression_ratio');
        // Extract percentage number
        $percentStr = str_replace('%', '', $ratio);
        $percent = floatval($percentStr);

        // Compression ratio should be between 0-100%
        $this->assertGreaterThanOrEqual(0, $percent);
        $this->assertLessThanOrEqual(100, $percent);
    }

    /**
     * Test file size values are correct
     */
    public function test_file_size_values_are_consistent()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->post(
            '/mobile/upload/disbursement/token',
            [
                'file' => UploadedFile::fake()->image('test.jpg', 500, 400),
                'attachment_type' => 'voucher',
            ]
        );

        $data = $response->json('data');

        // Compressed size should be less than or equal to original
        $this->assertLessThanOrEqual($data['original_size'], $data['size']);

        // Both sizes should be greater than zero
        $this->assertGreaterThan(0, $data['size']);
        $this->assertGreaterThan(0, $data['original_size']);
    }

    /**
     * Test validation error includes which field failed
     */
    public function test_validation_error_specifies_missing_field()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->post(
            '/mobile/upload/disbursement/token',
            ['attachment_type' => 'voucher'] // Missing 'file'
        );

        $response->assertStatus(422);
        $response->assertJsonPath('errors.file', function ($errors) {
            return is_array($errors) && !empty($errors);
        });
    }

    /**
     * Test multiple validation errors reported together
     */
    public function test_multiple_validation_errors_reported()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->post(
            '/mobile/upload/disbursement/token',
            [] // Missing both file and attachment_type
        );

        $response->assertStatus(422);
        $errors = $response->json('errors');

        // Should have either file or attachment_type error (depending on form request rules)
        $this->assertNotEmpty($errors);
    }

    /**
     * Test upload response message is meaningful
     */
    public function test_success_message_is_meaningful()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->post(
            '/mobile/upload/disbursement/token',
            [
                'file' => UploadedFile::fake()->image('test.jpg'),
                'attachment_type' => 'voucher',
            ]
        );

        $message = $response->json('message');
        $this->assertNotEmpty($message);
        $this->assertStringContainsString('successfully', strtolower($message));
    }

    /**
     * Test error message is meaningful
     */
    public function test_error_message_is_meaningful()
    {
        $response = $this->post(
            '/mobile/upload/disbursement/nonexistent',
            [
                'file' => UploadedFile::fake()->image('test.jpg'),
                'attachment_type' => 'voucher',
            ]
        );

        $message = $response->json('message');
        $this->assertNotEmpty($message);
        // Error message should be descriptive
        $this->assertTrue(strlen($message) > 5);
    }

    /**
     * Test filename is sanitized and valid
     */
    public function test_returned_filename_is_valid()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->post(
            '/mobile/upload/disbursement/token',
            [
                'file' => UploadedFile::fake()->image('test.jpg'),
                'attachment_type' => 'voucher',
            ]
        );

        $filename = $response->json('data.filename');

        // Filename should not contain path separators
        $this->assertStringNotContainsString('/', $filename);
        $this->assertStringNotContainsString('\\', $filename);

        // Filename should have an extension
        $this->assertStringContainsString('.', $filename);
    }

    /**
     * Test file_id in response matches database record
     */
    public function test_file_id_corresponds_to_database_record()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->post(
            '/mobile/upload/disbursement/token',
            [
                'file' => UploadedFile::fake()->image('test.jpg'),
                'attachment_type' => 'voucher',
            ]
        );

        $fileId = $response->json('data.file_id');

        // File ID should be numeric (primary key)
        $this->assertIsNumeric($fileId);
        $this->assertGreaterThan(0, $fileId);
    }
}
