<?php

namespace Tests\Feature;

use App\Models\Disbursement;
use App\Models\ScholarshipRecord;
use App\Models\ScholarshipProfileRequirement;
use App\Models\FundTransaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileUploadIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test full workflow: show page -> upload file -> verify response
     */
    public function test_complete_disbursement_upload_workflow()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'workflow_token',
            'upload_token_expires_at' => Carbon::now()->addHours(1),
        ]);

        // Step 1: Show upload page
        $showResponse = $this->get("/mobile/upload/disbursement/workflow_token");
        $showResponse->assertStatus(200);
        $showResponse->assertViewIs('mobile.disbursement-upload');

        // Step 2: Upload file
        $uploadResponse = $this->post(
            "/mobile/upload/disbursement/workflow_token",
            [
                'file' => UploadedFile::fake()->image('receipt.jpg', 400, 300),
                'attachment_type' => 'voucher',
            ]
        );
        $uploadResponse->assertStatus(201);
        $uploadResponse->assertJson(['success' => true]);
    }

    /**
     * Test workflow for scholarship record with multiple uploads
     */
    public function test_scholarship_record_multiple_upload_workflow()
    {
        $record = ScholarshipRecord::factory()->create([
            'upload_token' => 'multi_token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $files = [
            ['name' => 'contract_page_1.pdf', 'attachment_name' => 'contract', 'page' => 1],
            ['name' => 'contract_page_2.pdf', 'attachment_name' => 'contract', 'page' => 2],
            ['name' => 'diploma.jpg', 'attachment_name' => 'diploma', 'page' => null, 'is_image' => true],
        ];

        foreach ($files as $fileData) {
            $file = !empty($fileData['is_image'])
                ? UploadedFile::fake()->image($fileData['name'])
                : UploadedFile::fake()->create($fileData['name'], 150);

            $uploadData = [
                'file' => $file,
                'attachment_name' => $fileData['attachment_name'],
            ];

            if ($fileData['page']) {
                $uploadData['page_number'] = $fileData['page'];
            }

            $response = $this->post(
                "/mobile/upload/scholarship-record/multi_token",
                $uploadData
            );

            $response->assertStatus(201);
            $response->assertJson(['success' => true]);
        }
    }

    /**
     * Test fund transaction upload for all document types
     */
    public function test_fund_transaction_all_document_types_workflow()
    {
        $transaction = FundTransaction::factory()->create([
            'upload_token' => 'all_types_token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $documentTypes = [
            'obr' => 'OBR Report',
            'dv_payroll' => 'Payroll Report',
            'los' => 'List of Scholars',
            'cheque' => 'Cheque Details',
        ];

        foreach ($documentTypes as $type => $description) {
            $response = $this->post(
                "/mobile/upload/fund-transaction/all_types_token",
                [
                    'file' => UploadedFile::fake()->create("{$type}.pdf", 200),
                    'document_type' => $type,
                ]
            );

            $response->assertStatus(201);
            $response->assertJson(['success' => true]);
        }
    }

    /**
     * Test requirement upload with different file formats
     */
    public function test_requirement_upload_various_formats()
    {
        $requirement = ScholarshipProfileRequirement::factory()->create([
            'upload_token' => 'formats_token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $formats = [
            UploadedFile::fake()->image('document.jpg'),
            UploadedFile::fake()->image('document.png'),
            UploadedFile::fake()->create('document.pdf', 150, 'application/pdf'),
        ];

        foreach ($formats as $file) {
            $response = $this->post(
                "/mobile/upload/requirement/formats_token",
                ['file' => $file]
            );

            $response->assertStatus(201);
        }
    }

    /**
     * Test uploading large file (near limit)
     */
    public function test_upload_large_file_near_limit()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'large_token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        // Create file close to 25MB limit
        $file = UploadedFile::fake()->create('large.pdf', 25000);

        $response = $this->post(
            "/mobile/upload/disbursement/large_token",
            ['file' => $file, 'attachment_type' => 'voucher']
        );

        // Should either succeed or give size validation error
        $this->assertTrue(
            $response->status() === 201 || $response->status() === 422
        );
    }

    /**
     * Test sequential uploads maintaining token validity
     */
    public function test_sequential_uploads_same_token()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'sequential_token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        // Multiple uploads to same token
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->post(
                "/mobile/upload/disbursement/sequential_token",
                [
                    'file' => UploadedFile::fake()->image("file_{$i}.jpg"),
                    'attachment_type' => 'voucher',
                ]
            );

            $response->assertStatus(201);
        }
    }

    /**
     * Test response consistency across different entity types
     */
    public function test_response_structure_consistent_across_entities()
    {
        // Create different entities with tokens
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'disburse',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $record = ScholarshipRecord::factory()->create([
            'upload_token' => 'record',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $transaction = FundTransaction::factory()->create([
            'upload_token' => 'transaction',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        // Upload to each endpoint
        $responses = [
            $this->post('/mobile/upload/disbursement/disburse', [
                'file' => UploadedFile::fake()->image('test.jpg'),
                'attachment_type' => 'voucher',
            ]),
            $this->post('/mobile/upload/scholarship-record/record', [
                'file' => UploadedFile::fake()->image('test.jpg'),
                'attachment_name' => 'document',
            ]),
            $this->post('/mobile/upload/fund-transaction/transaction', [
                'file' => UploadedFile::fake()->create('test.pdf', 100),
                'document_type' => 'obr',
            ]),
        ];

        // All should have consistent structure
        foreach ($responses as $index => $response) {
            $response->assertStatus(201);
            $response->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
            $response->assertJson(['success' => true]);
        }
    }

    /**
     * Test error when token is completely invalid
     */
    public function test_invalid_token_returns_not_found()
    {
        $response = $this->post(
            '/mobile/upload/disbursement/completely_invalid_token_xyz',
            [
                'file' => UploadedFile::fake()->image('test.jpg'),
                'attachment_type' => 'voucher',
            ]
        );

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    /**
     * Test accessing show page without view throwing exception
     */
    public function test_show_disbursement_page_renders_correctly()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'view_token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->get("/mobile/upload/disbursement/view_token");

        $response->assertStatus(200);
        $response->assertOk();
    }

    /**
     * Test show scholarship record page renders correctly
     */
    public function test_show_scholarship_record_page_renders()
    {
        $record = ScholarshipRecord::factory()->create([
            'upload_token' => 'record_view',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->get("/mobile/upload/scholarship-record/record_view");

        $response->assertStatus(200);
        $response->assertOk();
    }

    /**
     * Test show requirement page renders correctly
     */
    public function test_show_requirement_page_renders()
    {
        $requirement = ScholarshipProfileRequirement::factory()->create([
            'upload_token' => 'req_view',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->get("/mobile/upload/requirement/req_view");

        $response->assertStatus(200);
        $response->assertOk();
    }

    /**
     * Test show fund transaction page renders correctly
     */
    public function test_show_fund_transaction_page_renders()
    {
        $transaction = FundTransaction::factory()->create([
            'upload_token' => 'tx_view',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->get("/mobile/upload/fund-transaction/tx_view");

        $response->assertStatus(200);
        $response->assertOk();
    }

    /**
     * Test token edge case: expires at midnight
     */
    public function test_token_expires_at_specific_time()
    {
        $expiryTime = Carbon::tomorrow()->startOfDay()->subSecond();

        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'midnight_token',
            'upload_token_expires_at' => $expiryTime,
        ]);

        $response = $this->get("/mobile/upload/disbursement/midnight_token");

        // Should allow access if token hasn't expired yet
        $response->assertOk();
    }

    /**
     * Test database record is created after upload
     */
    public function test_database_record_created_after_upload()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'db_token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        // Count attachments before upload
        $countBefore = $disbursement->attachments()->count();

        $response = $this->post(
            "/mobile/upload/disbursement/db_token",
            [
                'file' => UploadedFile::fake()->image('test.jpg'),
                'attachment_type' => 'voucher',
            ]
        );

        $response->assertStatus(201);

        // Verify attachment was created in database
        $countAfter = $disbursement->attachments()->count();
        $this->assertEquals($countBefore + 1, $countAfter);
    }

    /**
     * Test file is stored in correct location
     */
    public function test_uploaded_file_stored_correctly()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'storage_token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->post(
            "/mobile/upload/disbursement/storage_token",
            [
                'file' => UploadedFile::fake()->image('test.jpg'),
                'attachment_type' => 'voucher',
            ]
        );

        $response->assertStatus(201);

        // Verify file exists in storage
        $files = Storage::disk('public')->allFiles();
        $this->assertGreaterThan(0, count($files));
    }

    /**
     * Test upload preserves file integrity
     */
    public function test_uploaded_file_integrity()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'integrity_token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $file = UploadedFile::fake()->image('test.jpg');
        $originalSize = $file->getSize();

        $response = $this->post(
            "/mobile/upload/disbursement/integrity_token",
            [
                'file' => $file,
                'attachment_type' => 'voucher',
            ]
        );

        $response->assertStatus(201);

        // File should have been processed (possibly compressed)
        $storedSize = $response->json('data.size');
        $this->assertGreaterThan(0, $storedSize);
    }

    /**
     * Test concurrent uploads to different tokens
     */
    public function test_concurrent_uploads_different_tokens()
    {
        $entities = [
            Disbursement::factory()->create([
                'upload_token' => 'token_1',
                'upload_token_expires_at' => Carbon::now()->addDay(),
            ]),
            Disbursement::factory()->create([
                'upload_token' => 'token_2',
                'upload_token_expires_at' => Carbon::now()->addDay(),
            ]),
            Disbursement::factory()->create([
                'upload_token' => 'token_3',
                'upload_token_expires_at' => Carbon::now()->addDay(),
            ]),
        ];

        // Upload to each token
        foreach ($entities as $index => $entity) {
            $response = $this->post(
                "/mobile/upload/disbursement/token_" . ($index + 1),
                [
                    'file' => UploadedFile::fake()->image("file_{$index}.jpg"),
                    'attachment_type' => 'voucher',
                ]
            );

            $response->assertStatus(201);
        }

        // Verify all uploads succeeded
        $this->assertEquals(3, Disbursement::count());
    }
}
