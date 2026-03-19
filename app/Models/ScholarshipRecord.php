<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;

class ScholarshipRecord extends Model
{
    /** @use HasFactory<\Database\Factories\ScholarshipRecordFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'profile_id',
        'course_id',
        'program_id',
        'school_id',
        'term',
        'yakap_category',
        'yakap_location',
        'year_level',
        'academic_year',
        'school_name',
        'company_name',
        'start_date',
        'end_date',
        'remarks',
        'academic_status',
        'unified_status',
        'grant_provision',
        'is_active',
        'created_by',
        'updated_by',
        'date_filed',
        'date_approved',
        'upload_token',
        'upload_token_expires_at',
        'academic_potential',
        'financial_need_level',
        'communication_skills',
        'recommendation',
        'interview_remarks',
        'interviewed_by',
        'interviewed_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'date_filed' => 'date',
        'date_approved' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'upload_token_expires_at' => 'datetime',
        'interviewed_at' => 'datetime',
    ];

    /**
     * Boot the model
     * Automatically set created_by and updated_by on create/update
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set created_by and updated_by
        static::creating(function ($model) {
            $user = Auth::user();
            if ($user) {
                $model->created_by = $user->id;
                $model->updated_by = $user->id;
            }
        });

        static::updating(function ($model) {
            $user = Auth::user();
            if ($user) {
                $model->updated_by = $user->id;
            }
        });

        // unified_status is now the primary status field
        // No auto-generation needed as it's explicitly set
    }

    /**
     * Get scholarship_status value based on approval_status
     * Maps approval workflow status to scholarship status code
     * 
     * @param string|null $approvalStatus
     * @return int
     */
    public static function getScholarshipStatusFromApprovalStatus(?string $approvalStatus): int
    {
        return match ($approvalStatus) {
            'pending' => 0,           // Waiting list / Pending review
            'approved' => 1,          // Active / Approved
            'auto_approved' => 1,     // Active / Auto-approved
            'declined' => 2,          // Denied / Declined
            'conditional' => 0,       // Waiting list / Conditional approval (pending conditions)
            null => 0,                // No status yet
            default => 0,             // Default to waiting list
        };
    }

    /**
     * Generate unified status from old approval and scholarship status (for migration purposes)
     * Maps legacy status values to single unified status system
     */
    public static function generateUnifiedStatus(?string $approvalStatus, ?int $scholarshipStatus): string
    {
        // Denied takes priority
        if ($approvalStatus === 'denied' || $approvalStatus === 'declined') {
            return 'denied';
        }

        // Completed
        if ($scholarshipStatus === 3) {
            return 'completed';
        }

        // Active (approved and active)
        if ($approvalStatus === 'approved' || $approvalStatus === 'auto_approved') {
            return 'active';
        }

        // Approved (marked as approved but not yet activated)
        if ($approvalStatus === 'approved_pending') {
            return 'approved';
        }

        // Pending (new or unset status)
        if (in_array($approvalStatus, ['pending', 'conditionally-approved', 'conditional', null])) {
            return 'pending';
        }

        // Fallback for unrecognized statuses
        return 'unknown';
    }

    /**
     * Set date_filed - Handle timezone properly
     * When a date string is received from the frontend (e.g., "2026-01-12"),
     * it's in the user's local timezone (Asia/Manila).
     * We need to ensure it's stored correctly without timezone conversion.
     */
    public function setDateFiledAttribute($value)
    {
        if ($value) {
            // If it's a string date (YYYY-MM-DD format from frontend)
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                // Store as-is to preserve the date without timezone conversion
                $this->attributes['date_filed'] = $value;
            } else {
                // If it's a DateTime object or another format, convert to YYYY-MM-DD
                $this->attributes['date_filed'] = Carbon::parse($value)->format('Y-m-d');
            }
        } else {
            $this->attributes['date_filed'] = null;
        }
    }

    /**
     * Set date_approved - Handle timezone properly
     * When a date string is received from the frontend (e.g., "2026-01-12"),
     * it's in the user's local timezone (Asia/Manila).
     * We need to ensure it's stored correctly without timezone conversion.
     */
    public function setDateApprovedAttribute($value)
    {
        if ($value) {
            // If it's a string date (YYYY-MM-DD format from frontend)
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                // Store as-is to preserve the date without timezone conversion
                $this->attributes['date_approved'] = $value;
            } else {
                // If it's a DateTime object or another format, convert to YYYY-MM-DD
                $this->attributes['date_approved'] = Carbon::parse($value)->format('Y-m-d');
            }
        } else {
            $this->attributes['date_approved'] = null;
        }
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name']);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select(['id', 'name']);
    }

    public function interviewer()
    {
        return $this->belongsTo(User::class, 'interviewed_by')->select(['id', 'name']);
    }

    public function course()
    {
        return $this->hasOne(Course::class, 'id', 'course_id');
    }

    public function school()
    {
        return $this->hasOne(School::class, 'id', 'school_id');
    }

    public function program()
    {
        return $this->hasOneThrough(
            ScholarshipProgram::class, // Final model
            Course::class,  // Intermediate model
            'id',           // Foreign key on courses table
            'id',           // Foreign key on programs table
            'course_id',    // Local key on scholars table
            'scholarship_program_id'    // Local key on courses table
        );
    }

    public function requirements()
    {
        return $this->hasMany(ScholarshipRecordRequirement::class, 'record_id');
    }

    public function requirement()
    {
        return $this->belongsTo(Requirement::class);
    }

    public function profile()
    {
        return $this->belongsTo(ScholarshipProfile::class, 'profile_id');
    }

    // Enhanced workflow relationships
    public function previousScholarship()
    {
        return $this->belongsTo(ScholarshipRecord::class, 'previous_scholarship_id', 'id');
    }

    public function nextApplications()
    {
        return $this->hasMany(ScholarshipRecord::class, 'previous_scholarship_id', 'id');
    }

    public function completion()
    {
        return $this->hasOne(ScholarshipCompletion::class, 'scholarship_record_id', 'id');
    }

    public function attachments()
    {
        return $this->hasMany(ScholarshipRecordAttachment::class, 'scholarship_record_id', 'id');
    }

    public function approvedBy()
    {
        // Deprecated: approved_by field removed in migration
        throw new \BadMethodCallException('approvedBy() is deprecated. Use approvalHistory() instead.');
    }

    public function declinedBy()
    {
        // Deprecated: declined_by field removed in migration
        throw new \BadMethodCallException('declinedBy() is deprecated. Use approvalHistory() instead.');
    }

    public function resubmissionAllowedBy()
    {
        // Deprecated: resubmission_allowed_by field removed in migration
        throw new \BadMethodCallException('resubmissionAllowedBy() is deprecated. Resubmission workflow no longer supported.');
    }

    public function approvalHistory()
    {
        return $this->hasMany(ScholarshipApprovalHistory::class, 'scholarship_record_id', 'id');
    }

    // Status helper methods - Now using unified_status
    public function getUnifiedStatusConfig()
    {
        $statusConfig = [
            'pending' => ['label' => 'Pending', 'color' => 'warning'],
            'approved' => ['label' => 'Approved', 'color' => 'info'],
            'denied' => ['label' => 'Denied', 'color' => 'danger'],
            'active' => ['label' => 'Active', 'color' => 'success'],
            'completed' => ['label' => 'Completed', 'color' => 'secondary'],
            'unknown' => ['label' => 'Unknown', 'color' => 'secondary'],
        ];
        return $statusConfig[$this->unified_status] ?? null;
    }

    public function getUnifiedStatusLabel()
    {
        return $this->getUnifiedStatusConfig()['label'] ?? ucfirst($this->unified_status);
    }

    public function getUnifiedStatusColor()
    {
        return $this->getUnifiedStatusConfig()['color'] ?? 'secondary';
    }

    // Status checks - Using unified_status
    public function isPending()
    {
        return $this->unified_status === 'pending';
    }

    public function isApproved()
    {
        return $this->unified_status === 'approved';
    }

    public function isDenied()
    {
        return $this->unified_status === 'denied';
    }

    public function isActive()
    {
        return $this->unified_status === 'active';
    }

    public function isCompleted()
    {
        return $this->unified_status === 'completed';
    }

    public function isDiscontinued()
    {
        // Deprecated: completion_status field removed in migration
        throw new \BadMethodCallException('isDiscontinued() is deprecated. Use unified_status === "completed" instead.');
    }

    public function canBeModified()
    {
        return in_array($this->unified_status, ['pending', 'approved']);
    }

    public function canBeResubmitted()
    {
        // Deprecated: resubmission workflow fields removed in migration
        throw new \BadMethodCallException('canBeResubmitted() is deprecated. Resubmission workflow no longer supported.');
    }

    public function shouldAutoApprove()
    {
        $autoApprovalConfig = config('scholarship.auto_approval');

        if (!$autoApprovalConfig['enabled']) {
            return false;
        }

        // Check if this status allows auto-approval
        $statusConfig = $this->getApprovalStatusConfig();
        if (!$statusConfig || !($statusConfig['auto_approve'] ?? false)) {
            return false;
        }

        // Check grade threshold if completion exists
        if ($this->completion && $this->completion->final_grade) {
            $threshold = $autoApprovalConfig['conditions']['grade_threshold'] ?? 2.5;
            if ($this->completion->final_grade > $threshold) {
                return false;
            }
        }

        return true;
    }

    // Scopes for querying using unified_status
    public function scopeByUnifiedStatus($query, $status)
    {
        return $query->where('unified_status', $status);
    }

    public function scopeByCompletionStatus($query, $status)
    {
        // Deprecated: completion_status field removed in migration
        throw new \BadMethodCallException('scopeByCompletionStatus() is deprecated. Use unified_status queries instead.');
    }

    public function scopePending($query)
    {
        return $query->where('unified_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('unified_status', 'approved');
    }

    public function scopeDenied($query)
    {
        return $query->where('unified_status', 'denied');
    }

    public function scopeActive($query)
    {
        return $query->where('unified_status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('unified_status', 'completed');
    }

    public function scopeRenewalApplications($query)
    {
        return $query->whereNotNull('previous_scholarship_id');
    }

    public function updateRemarks($remarks)
    {
        $this->remarks = $remarks;
        return $this->save();
    }

    /**
     * Generate a unique upload token for mobile upload
     */
    public function generateUploadToken($expiresInMinutes = 15)
    {
        $expiryTime = now()->addMinutes($expiresInMinutes);

        $this->upload_token = Str::random(64);
        $this->upload_token_expires_at = $expiryTime;

        Log::info('Generating upload token', [
            'model' => 'ScholarshipRecord',
            'id' => $this->id,
            'expires_in_minutes' => $expiresInMinutes,
            'now' => now()->toDateTimeString(),
            'expiry_time' => $expiryTime->toDateTimeString(),
            'token_preview' => substr($this->upload_token, 0, 10) . '...',
        ]);

        $this->save();
        $this->refresh(); // Refresh to get exact database value

        Log::info('Upload token saved and refreshed', [
            'model' => 'ScholarshipRecord',
            'id' => $this->id,
            'expires_at_after_save' => $this->upload_token_expires_at->toDateTimeString(),
            'now' => now()->toDateTimeString(),
            'seconds_until_expiry' => now()->diffInSeconds($this->upload_token_expires_at, false),
        ]);

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

        return $baseUrl . '/mobile/upload/scholarship-record/' . $this->upload_token;
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
