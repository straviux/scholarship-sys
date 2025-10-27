<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Disbursement extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'disbursement_id';

    protected $fillable = [
        'profile_id',
        'disbursement_type',
        'payee',
        'obr_no',
        'obr_status',
        'date_obligated',
        'year_level',
        'semester',
        'academic_year',
        'amount',
        'remarks',
        'created_by',
        'upload_token',
        'upload_token_expires_at',
    ];

    protected $casts = [
        'date_obligated' => 'date',
        'amount' => 'decimal:2',
        'upload_token_expires_at' => 'datetime',
    ];

    /**
     * Get the profile that owns the disbursement.
     */
    public function profile()
    {
        return $this->belongsTo(ScholarshipProfile::class, 'profile_id', 'profile_id');
    }

    /**
     * Get the user who created the disbursement.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the cheques for the disbursement.
     */
    public function cheques()
    {
        return $this->hasMany(Cheque::class, 'disbursement_id', 'disbursement_id');
    }

    /**
     * Get the latest cheque for the disbursement.
     */
    public function latestCheque()
    {
        return $this->hasOne(Cheque::class, 'disbursement_id', 'disbursement_id')->latest();
    }

    /**
     * Get the attachments for the disbursement.
     */
    public function attachments()
    {
        return $this->hasMany(DisbursementAttachment::class, 'disbursement_id', 'disbursement_id');
    }

    /**
     * Generate a unique upload token for mobile upload
     */
    public function generateUploadToken($expiresInMinutes = 15)
    {
        $this->upload_token = Str::random(64);
        $this->upload_token_expires_at = now()->addMinutes($expiresInMinutes);
        $this->save();
        $this->refresh(); // Refresh to get exact database value

        return $this->upload_token;
    }

    /**
     * Get the mobile upload URL
     */
    public function getMobileUploadUrl()
    {
        if (!$this->upload_token || $this->upload_token_expires_at < now()) {
            $this->generateUploadToken();
        }

        // Auto-detect base URL from current request, fallback to APP_URL
        if (request()->getSchemeAndHttpHost()) {
            $baseUrl = request()->getSchemeAndHttpHost();
        } else {
            $baseUrl = config('app.url');
        }

        return $baseUrl . '/mobile/upload/disbursement/' . $this->upload_token;
    }

    /**
     * Generate QR code for mobile upload
     */
    public function getUploadQrCode($size = 200)
    {
        $url = $this->getMobileUploadUrl();
        $qrCode = QrCode::size($size)->generate($url);
        return (string) $qrCode;
    }

    /**
     * Get QR code as base64 data URI
     */
    public function getUploadQrCodeDataUri($size = 200)
    {
        $url = $this->getMobileUploadUrl();
        $qrCode = QrCode::format('png')->size($size)->generate($url);
        return 'data:image/png;base64,' . base64_encode($qrCode);
    }
}
