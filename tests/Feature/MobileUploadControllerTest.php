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

class MobileUploadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // ========== Disbursement Upload Tests ==========

    /**
     * Test showing disbursement upload page with valid token
     */
    public function test_show_disbursement_upload_with_valid_token()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'valid_disbursement_token',
            'upload_token_expires_at' => Carbon::now()->addHours(2),
        ]);

        $response = $this->get("/mobile/upload/disbursement/valid_disbursement_token");

        $response->assertStatus(200);
        $response->assertViewIs('mobile.disbursement-upload');
        $response->assertViewHas('disbursement');
    }

    /**
     * Test showing disbursement upload with expired token
     */
    public function test_show_disbursement_upload_with_expired_token()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'expired_token',
            'upload_token_expires_at' => Carbon::now()->subHours(1),
        ]);

        $response = $this->get("/mobile/upload/disbursement/expired_token");

        $response->assertStatus(200);
        $response->assertViewIs('mobile.upload-expired');
    }

    /**
     * Test showing disbursement upload with invalid token
     */
    public function test_show_disbursement_upload_with_invalid_token()
    {
        $response = $this->get("/mobile/upload/disbursement/nonexistent_token");

        $response->assertStatus(200);
        $response->assertViewIs('mobile.upload-expired');
    }

    /**
     * Test uploading disbursement file successfully
     */
    public function test_upload_disbursement_file_successfully()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'disbursement_token',
            'upload_token_expires_at' => Carbon::now()->addHours(1),
        ]);

        $file = UploadedFile::fake()->image('receipt.jpg', 400, 300);

        $response = $this->post(
            "/mobile/upload/disbursement/disbursement_token",
            [
                'file' => $file,
                'attachment_type' => 'voucher',
            ]
        );

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('message', 'File uploaded successfully');
    }

    /**
     * Test uploading disbursement file with different attachment types
     */
    public function test_upload_disbursement_with_different_types()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $types = ['voucher', 'cheque', 'receipt'];

        foreach ($types as $type) {
            $file = UploadedFile::fake()->image('file.jpg');

            $response = $this->post(
                "/mobile/upload/disbursement/token",
                ['file' => $file, 'attachment_type' => $type]
            );

            $response->assertStatus(201);
        }
    }

    /**
     * Test uploading without required file
     */
    public function test_upload_disbursement_without_file()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->post(
            "/mobile/upload/disbursement/token",
            ['attachment_type' => 'voucher']
        );

        $response->assertStatus(422);
    }

    /**
     * Test uploading disbursement with expired token
     */
    public function test_upload_disbursement_with_expired_token()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'expired_token',
            'upload_token_expires_at' => Carbon::now()->subHour(),
        ]);

        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->post(
            "/mobile/upload/disbursement/expired_token",
            ['file' => $file, 'attachment_type' => 'voucher']
        );

        $response->assertStatus(422);
    }

    /**
     * Test uploading oversized file
     */
    public function test_upload_disbursement_oversized_file()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $file = UploadedFile::fake()->create('large.jpg', 30000); // 30MB

        $response = $this->post(
            "/mobile/upload/disbursement/token",
            ['file' => $file, 'attachment_type' => 'voucher']
        );

        $response->assertStatus(422);
    }

    // ========== Scholarship Record Upload Tests ==========

    /**
     * Test showing scholarship record upload with valid token
     */
    public function test_show_scholarship_record_upload_with_valid_token()
    {
        $record = ScholarshipRecord::factory()->create([
            'upload_token' => 'record_token',
            'upload_token_expires_at' => Carbon::now()->addHours(2),
        ]);

        $response = $this->get("/mobile/upload/scholarship-record/record_token");

        $response->assertStatus(200);
        $response->assertViewIs('mobile.scholarship-record-upload');
        $response->assertViewHas('scholarshipRecord');
    }

    /**
     * Test showing scholarship record with expired token
     */
    public function test_show_scholarship_record_with_expired_token()
    {
        $record = ScholarshipRecord::factory()->create([
            'upload_token' => 'expired_record_token',
            'upload_token_expires_at' => Carbon::now()->subDay(),
        ]);

        $response = $this->get("/mobile/upload/scholarship-record/expired_record_token");

        $response->assertStatus(200);
        $response->assertViewIs('mobile.upload-expired');
    }

    /**
     * Test uploading scholarship record file successfully
     */
    public function test_upload_scholarship_record_file_successfully()
    {
        $record = ScholarshipRecord::factory()->create([
            'upload_token' => 'record_token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $file = UploadedFile::fake()->image('contract.jpg');

        $response = $this->post(
            "/mobile/upload/scholarship-record/record_token",
            [
                'file' => $file,
                'attachment_name' => 'contract',
                'page_number' => 1,
            ]
        );

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test uploading scholarship record without page number
     */
    public function test_upload_scholarship_record_without_page_number()
    {
        $record = ScholarshipRecord::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->post(
            "/mobile/upload/scholarship-record/token",
            [
                'file' => $file,
                'attachment_name' => 'receipt',
            ]
        );

        $response->assertStatus(201);
    }

    /**
     * Test uploading with multiple page numbers
     */
    public function test_upload_scholarship_record_multiple_pages()
    {
        $record = ScholarshipRecord::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        for ($page = 1; $page <= 3; $page++) {
            $file = UploadedFile::fake()->create("contract_page_{$page}.pdf", 150);

            $response = $this->post(
                "/mobile/upload/scholarship-record/token",
                [
                    'file' => $file,
                    'attachment_name' => 'contract',
                    'page_number' => $page,
                ]
            );

            $response->assertStatus(201);
        }
    }

    // ========== Requirement Upload Tests ==========

    /**
     * Test showing requirement upload with valid token
     */
    public function test_show_requirement_upload_with_valid_token()
    {
        $requirement = ScholarshipProfileRequirement::factory()->create([
            'upload_token' => 'req_token',
            'upload_token_expires_at' => Carbon::now()->addHours(2),
        ]);

        $response = $this->get("/mobile/upload/requirement/req_token");

        $response->assertStatus(200);
        $response->assertViewIs('mobile.requirement-upload');
        $response->assertViewHas('requirement');
    }

    /**
     * Test uploading requirement file successfully
     */
    public function test_upload_requirement_file_successfully()
    {
        $requirement = ScholarshipProfileRequirement::factory()->create([
            'upload_token' => 'req_token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $file = UploadedFile::fake()->image('document.jpg');

        $response = $this->post(
            "/mobile/upload/requirement/req_token",
            ['file' => $file]
        );

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test uploading requirement with invalid file type
     */
    public function test_upload_requirement_invalid_file_type()
    {
        $requirement = ScholarshipProfileRequirement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $file = UploadedFile::fake()->create('script.exe', 100);

        $response = $this->post(
            "/mobile/upload/requirement/token",
            ['file' => $file]
        );

        $response->assertStatus(422);
    }

    // ========== Fund Transaction Upload Tests ==========

    /**
     * Test showing fund transaction upload with valid token
     */
    public function test_show_fund_transaction_upload_with_valid_token()
    {
        $transaction = FundTransaction::factory()->create([
            'upload_token' => 'fund_token',
            'upload_token_expires_at' => Carbon::now()->addHours(2),
        ]);

        $response = $this->get("/mobile/upload/fund-transaction/fund_token");

        $response->assertStatus(200);
        $response->assertViewIs('mobile.fund-transaction-upload');
        $response->assertViewHas('transaction');
    }

    /**
     * Test showing fund transaction with specific document type
     */
    public function test_show_fund_transaction_with_doc_type()
    {
        $transaction = FundTransaction::factory()->create([
            'upload_token' => 'fund_token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->get("/mobile/upload/fund-transaction/fund_token/obr");

        $response->assertStatus(200);
        $response->assertViewIs('mobile.fund-transaction-upload');
        $response->assertViewHas('docType', 'obr');
    }

    /**
     * Test showing fund transaction with invalid document type
     */
    public function test_show_fund_transaction_with_invalid_doc_type()
    {
        $transaction = FundTransaction::factory()->create([
            'upload_token' => 'fund_token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->get("/mobile/upload/fund-transaction/fund_token/invalid");

        $response->assertStatus(200);
        $response->assertViewHas('docType', null);
    }

    /**
     * Test uploading fund transaction document successfully
     */
    public function test_upload_fund_transaction_file_successfully()
    {
        $transaction = FundTransaction::factory()->create([
            'upload_token' => 'fund_token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $file = UploadedFile::fake()->create('obr_document.pdf', 200);

        $response = $this->post(
            "/mobile/upload/fund-transaction/fund_token",
            [
                'file' => $file,
                'document_type' => 'obr',
            ]
        );

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test uploading with all valid document types
     */
    public function test_upload_fund_transaction_all_types()
    {
        $transaction = FundTransaction::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $types = ['obr', 'dv_payroll', 'los', 'cheque'];

        foreach ($types as $type) {
            $file = UploadedFile::fake()->create("{$type}.pdf", 150);

            $response = $this->post(
                "/mobile/upload/fund-transaction/token",
                ['file' => $file, 'document_type' => $type]
            );

            $response->assertStatus(201);
        }
    }

    /**
     * Test uploading fund transaction with invalid document type
     */
    public function test_upload_fund_transaction_invalid_type()
    {
        $transaction = FundTransaction::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->post(
            "/mobile/upload/fund-transaction/token",
            ['file' => $file, 'document_type' => 'invalid_type']
        );

        $response->assertStatus(422);
    }

    /**
     * Test uploading fund transaction with expired token
     */
    public function test_upload_fund_transaction_expired_token()
    {
        $transaction = FundTransaction::factory()->create([
            'upload_token' => 'expired',
            'upload_token_expires_at' => Carbon::now()->subDay(),
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->post(
            "/mobile/upload/fund-transaction/expired",
            ['file' => $file, 'document_type' => 'obr']
        );

        $response->assertStatus(422);
    }

    // ========== Response Format Tests ==========

    /**
     * Test successful response format
     */
    public function test_successful_response_format()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->post(
            "/mobile/upload/disbursement/token",
            ['file' => $file, 'attachment_type' => 'voucher']
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
    }

    /**
     * Test error response format
     */
    public function test_error_response_format()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->post(
            "/mobile/upload/disbursement/token",
            ['attachment_type' => 'voucher']
        );

        $response->assertJsonStructure([
            'success',
            'message',
            'errors',
        ]);
    }

    // ========== Token Expiry Edge Cases ==========

    /**
     * Test token expiring in 1 minute
     */
    public function test_token_expiring_very_soon()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addMinutes(1),
        ]);

        $response = $this->get("/mobile/upload/disbursement/token");

        $response->assertStatus(200);
        $response->assertViewIs('mobile.disbursement-upload');
    }

    /**
     * Test token expiring just now
     */
    public function test_token_expiring_just_now()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now(),
        ]);

        $response = $this->get("/mobile/upload/disbursement/token");

        $response->assertStatus(200);
        $response->assertViewIs('mobile.upload-expired');
    }

    // ========== File Type Tests ==========

    /**
     * Test uploading PNG image
     */
    public function test_upload_png_image()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $file = UploadedFile::fake()->image('photo.png');

        $response = $this->post(
            "/mobile/upload/disbursement/token",
            ['file' => $file, 'attachment_type' => 'voucher']
        );

        $response->assertStatus(201);
    }

    /**
     * Test uploading JPEG image
     */
    public function test_upload_jpeg_image()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $file = UploadedFile::fake()->image('photo.jpeg');

        $response = $this->post(
            "/mobile/upload/disbursement/token",
            ['file' => $file, 'attachment_type' => 'voucher']
        );

        $response->assertStatus(201);
    }

    /**
     * Test uploading PDF
     */
    public function test_upload_pdf()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 200, 'application/pdf');

        $response = $this->post(
            "/mobile/upload/disbursement/token",
            ['file' => $file, 'attachment_type' => 'voucher']
        );

        $response->assertStatus(201);
    }

    // ========== Multiple Uploads Tests ==========

    /**
     * Test uploading multiple files for same entity
     */
    public function test_upload_multiple_files_same_entity()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $file = UploadedFile::fake()->image("photo_{$i}.jpg");

            $response = $this->post(
                "/mobile/upload/disbursement/token",
                ['file' => $file, 'attachment_type' => 'voucher']
            );

            $response->assertStatus(201);
        }
    }

    /**
     * Test response includes compression info
     */
    public function test_response_includes_compression_info()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $response = $this->post(
            "/mobile/upload/disbursement/token",
            ['file' => $file, 'attachment_type' => 'voucher']
        );

        $response->assertStatus(201);
        $response->assertJsonPath('data.compression_ratio', function ($ratio) {
            return is_string($ratio) && strpos($ratio, '%') !== false;
        });
    }
}
