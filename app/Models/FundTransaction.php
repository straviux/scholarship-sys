<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Helpers\MobileUploadUrl;

class FundTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fund_transactions';

    protected $fillable = [
        'voucher_number',
        'voucher_type',
        'explanation',
        'los_course',
        'course',
        'academic_year',
        'semester',
        'year_level',
        'obr_type',
        'payee_type',
        'payee_name',
        'payee_address',
        'responsibility_center',
        'account_code',
        'particulars_name',
        'particulars_description',
        'amount',
        'scholar_ids',
        'notes',
        'remarks',
        'transaction_status',
        'school',
        'grant_provision',
        'created_by',
        'updated_by',
        'fiscal_year',
        'obr_no',
        'dv_no',
        'date_obligated',
        'upload_token',
        'upload_token_expires_at',
    ];

    protected $casts = [
        'scholar_ids' => 'array',
        'particulars_description' => 'array',
        'amount' => 'decimal:2',
        'upload_token_expires_at' => 'datetime',
    ];

    protected $appends = ['obr_status'];

    /**
     * Get obr_status as an alias for transaction_status.
     */
    public function getObrStatusAttribute()
    {
        return $this->attributes['transaction_status'] ?? null;
    }

    /**
     * Set obr_status by setting transaction_status.
     */
    public function setObrStatusAttribute($value)
    {
        $this->attributes['transaction_status'] = $value;
    }

    /**
     * Get the user who created the voucher.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Get the associated scholars.
     *
     * NOTE: This is NOT an Eloquent relationship. It returns a Collection
     * based on the scholar_ids JSON column. Do not use with eager loading.
     *
     * @return \Illuminate\Support\Collection|array
     */
    public function getScholars()
    {
        if (empty($this->scholar_ids)) {
            return collect();
        }
        return ScholarshipProfile::whereIn('profile_id', $this->scholar_ids)->get();
    }

    /**
     * Get the responsibility center.
     */
    public function responsibilityCenter()
    {
        return $this->belongsTo(ResponsibilityCenter::class, 'responsibility_center', 'id');
    }

    /**
     * Get all documents for this fund transaction
     */
    public function documents()
    {
        return $this->hasMany(FundTransactionDocument::class, 'fund_transaction_id');
    }

    /**
     * Get a specific document by type
     */
    public function getDocument($documentType)
    {
        return $this->documents()->where('document_type', $documentType)->first();
    }

    /**
     * Generate upload token for QR-based document uploads
     */
    public function generateUploadToken($expiresInMinutes = 5)
    {
        $this->upload_token = Str::random(64);
        $this->upload_token_expires_at = now()->addMinutes($expiresInMinutes);
        $this->save();
        return $this->upload_token;
    }

    /**
     * Get the mobile upload URL
     */
    public function getMobileUploadUrl($docType = null)
    {
        if ($docType) {
            return MobileUploadUrl::build('mobile.upload.fund-transaction.with-type', ['token' => $this->upload_token, 'doc_type' => $docType]);
        }
        return MobileUploadUrl::build('mobile.upload.fund-transaction', $this->upload_token);
    }

    /**
     * Get the upload QR code as SVG
     */
    public function getUploadQrCode($size = 250, $docType = null)
    {
        $qrCode = QrCode::format('svg')
            ->size($size)
            ->generate($this->getMobileUploadUrl($docType));

        // Ensure we return a string, not an object
        return is_string($qrCode) ? $qrCode : (string) $qrCode;
    }

    /**
     * Get the upload QR code as data URI (base64 PNG)
     */
    public function getUploadQrCodeDataUri($size = 250, $docType = null)
    {
        $qrCode = QrCode::format('png')
            ->size($size)
            ->generate($this->getMobileUploadUrl($docType));

        return is_string($qrCode) ? $qrCode : (string) $qrCode;
    }
}
