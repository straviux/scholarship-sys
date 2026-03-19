<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipProfileRequirement extends Model
{
    use HasFactory;

    protected $table = 'scholarship_profile_requirements';

    protected $fillable = [
        'profile_id',
        'requirement_id',
        'file_name',
        'file_path',
        'upload_token',
        'upload_token_expires_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'upload_token_expires_at' => 'datetime',
    ];

    public function profile()
    {
        return $this->belongsTo(ScholarshipProfile::class, 'profile_id');
    }

    public function requirement()
    {
        return $this->belongsTo(Requirement::class, 'requirement_id');
    }

    /**
     * Generate upload token for mobile requirement file upload.
     *
     * @param int $expiresInMinutes Token expiration time in minutes
     * @return string
     */
    public function generateUploadToken($expiresInMinutes = 5): string
    {
        $this->update([
            'upload_token' => \Illuminate\Support\Str::random(32),
            'upload_token_expires_at' => now()->addMinutes($expiresInMinutes),
        ]);

        return $this->upload_token;
    }

    /**
     * Get the mobile upload URL for this requirement.
     *
     * @return string
     */
    public function getMobileUploadUrl(): string
    {
        // Generate token if it doesn't exist or has expired
        if (!$this->upload_token || $this->upload_token_expires_at < now()) {
            $this->generateUploadToken();
        }

        $baseUrl = request()->getSchemeAndHttpHost() ?: config('app.url');

        // If localhost or 127.0.0.1, try to get the server's actual IP
        if (stripos($baseUrl, 'localhost') !== false || stripos($baseUrl, '127.0.0.1') !== false) {
            // Try to use server's external IP
            $serverIp = gethostbyname(gethostname());
            if ($serverIp && $serverIp !== gethostname()) {
                // Extract port if present in original URL
                $port = parse_url($baseUrl, PHP_URL_PORT);
                $portString = $port ? ':' . $port : ':8000';
                $baseUrl = 'http://' . $serverIp . $portString;
            }
        }

        // Force http scheme
        $baseUrl = preg_replace('/^https:/i', 'http:', $baseUrl);
        return $baseUrl . '/mobile/upload/requirement/' . $this->upload_token;
    }

    /**
     * Generate QR code for mobile requirement file upload.
     *
     * @param int $size QR code size in pixels
     * @return string SVG QR code
     */
    public function getUploadQrCode($size = 200): string
    {
        $url = $this->getMobileUploadUrl();
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::size($size)->generate($url);

        return (string) $qrCode;
    }
}
