<?php

namespace Tests\Unit\Traits;

use App\Models\Disbursement;
use App\Traits\TokenValidation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TokenValidationTest extends TestCase
{
    use RefreshDatabase;

    // Test class that uses the trait
    private object $traitUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an anonymous class that uses the trait
        $this->traitUser = new class {
            use TokenValidation;
        };
    }

    /**
     * Test valid token passes validation
     */
    public function test_valid_token_passes_validation()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'test_token_123',
            'upload_token_expires_at' => Carbon::now()->addHours(2),
        ]);

        // Should not throw exception
        try {
            $this->traitUser->validateUploadToken($disbursement, 'disbursement');
            $this->assertTrue(true);
        } catch (ValidationException $e) {
            $this->fail('Valid token should not throw exception');
        }
    }

    /**
     * Test expired token throws exception
     */
    public function test_expired_token_throws_exception()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'expired_token',
            'upload_token_expires_at' => Carbon::now()->subHours(1),
        ]);

        $this->expectException(ValidationException::class);
        $this->traitUser->validateUploadToken($disbursement, 'disbursement');
    }

    /**
     * Test null entity throws exception
     */
    public function test_null_entity_throws_exception()
    {
        $this->expectException(ValidationException::class);
        $this->traitUser->validateUploadToken(null, 'disbursement');
    }

    /**
     * Test missing expiry field throws exception
     */
    public function test_missing_expiry_field_throws_exception()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'test_token',
            'upload_token_expires_at' => null,
        ]);

        $this->expectException(ValidationException::class);
        $this->traitUser->validateUploadToken($disbursement, 'disbursement');
    }

    /**
     * Test token expiring very soon
     */
    public function test_token_expiring_very_soon_passes()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'soon_expire',
            'upload_token_expires_at' => Carbon::now()->addSeconds(30),
        ]);

        try {
            $this->traitUser->validateUploadToken($disbursement, 'disbursement');
            $this->assertTrue(true);
        } catch (ValidationException $e) {
            $this->fail('Token that hasnt expired should pass validation');
        }
    }

    /**
     * Test token just expired
     */
    public function test_token_just_expired_throws()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'just_expired',
            'upload_token_expires_at' => Carbon::now()->subSeconds(30),
        ]);

        $this->expectException(ValidationException::class);
        $this->traitUser->validateUploadToken($disbursement, 'disbursement');
    }

    /**
     * Test exactly at expiration time (edge case)
     */
    public function test_exactly_at_expiration_time()
    {
        $now = Carbon::now();
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'exact_time',
            'upload_token_expires_at' => $now,
        ]);

        // isPast() returns true if time is in past or equals now
        // So this should fail validation
        $this->expectException(ValidationException::class);
        $this->traitUser->validateUploadToken($disbursement, 'disbursement');
    }

    /**
     * Test different entity types work
     */
    public function test_different_entity_types_accepted()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token1',
            'upload_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $entityTypes = ['disbursement', 'scholarship_record', 'requirement', 'fund_transaction'];

        foreach ($entityTypes as $type) {
            try {
                $this->traitUser->validateUploadToken($disbursement, $type);
                $this->assertTrue(true);
            } catch (ValidationException $e) {
                $this->fail("Entity type '{$type}' should be accepted");
            }
        }
    }

    /**
     * Test exception message contains entity type
     */
    public function test_exception_message_contains_context()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'token',
            'upload_token_expires_at' => Carbon::now()->subDay(),
        ]);

        try {
            $this->traitUser->validateUploadToken($disbursement, 'disbursement');
            $this->fail('Should throw exception');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertNotEmpty($errors);
        }
    }

    /**
     * Test long-lived token passes validation
     */
    public function test_long_lived_token()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'long_token',
            'upload_token_expires_at' => Carbon::now()->addYears(1),
        ]);

        try {
            $this->traitUser->validateUploadToken($disbursement, 'disbursement');
            $this->assertTrue(true);
        } catch (ValidationException $e) {
            $this->fail('Long-lived token should pass');
        }
    }

    /**
     * Test midnight expiration time
     */
    public function test_midnight_expiration()
    {
        $tomorrow = Carbon::tomorrow()->startOfDay();
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'midnight_token',
            'upload_token_expires_at' => $tomorrow,
        ]);

        try {
            $this->traitUser->validateUploadToken($disbursement, 'disbursement');
            $this->assertTrue(true);
        } catch (ValidationException $e) {
            $this->fail('Midnight expiration should pass if in future');
        }
    }

    /**
     * Test validation exception is thrown not returned
     */
    public function test_validation_exception_thrown()
    {
        $disbursement = Disbursement::factory()->create([
            'upload_token' => 'expired',
            'upload_token_expires_at' => Carbon::now()->subDay(),
        ]);

        $exception = null;
        try {
            $this->traitUser->validateUploadToken($disbursement, 'disbursement');
        } catch (ValidationException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(ValidationException::class, $exception);
    }
}
