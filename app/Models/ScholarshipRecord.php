<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ScholarshipRecord extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicantFactory> */
    use HasFactory;

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
        'scholarship_status',
        'scholarship_status_remarks',
        'grant_provision',
        'scholarship_status_date',
        'is_active',
        'created_by',
        'updated_by',
        'date_filed',
        'date_approved',
        // Enhanced workflow fields
        'application_cycle',
        'previous_scholarship_id',
        'completion_status',
        'completion_date',
        'completion_remarks',
        'next_degree_level',
        'resubmitted_at',
        'resubmission_notes',
        'resubmission_allowed_by',
        'resubmission_allowed_at',
        'resubmission_deadline',
        'resubmission_requirements',
        'resubmission_count',
        'approval_status',
        'approved_by',
        'approved_at',
        'declined_by',
        'declined_at',
        'approval_remarks',
        'decline_reason',
        'conditional_requirements',
        'conditional_deadline',
        'conditional_deadline_notified_at',
        'conditional_deadline_expired',
        'upload_token',
        'upload_token_expires_at',
        // Note: application_status, application_status_remarks, application_status_date removed (redundant with scholarship_status)
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'completion_date' => 'date',
        'resubmitted_at' => 'datetime',
        'resubmission_allowed_at' => 'datetime',
        'resubmission_deadline' => 'datetime',
        'approved_at' => 'datetime',
        'declined_at' => 'datetime',
        'conditional_requirements' => 'array',
        'conditional_deadline' => 'datetime',
        'conditional_deadline_notified_at' => 'datetime',
        'conditional_deadline_expired' => 'boolean',
        'date_filed' => 'date',
        'date_approved' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'upload_token_expires_at' => 'datetime',
    ];

    /**
     * Boot the model
     * Automatically update scholarship_status when approval_status changes
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically sync scholarship_status based on approval_status
        static::saving(function ($model) {
            if ($model->isDirty('approval_status')) {
                $model->scholarship_status = self::getScholarshipStatusFromApprovalStatus(
                    $model->approval_status
                );
            }
        });
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
     * Check if scholarship is on waiting list
     * @return bool
     */
    public function isOnWaitingList(): bool
    {
        return $this->scholarship_status === 0;
    }

    /**
     * Check if scholarship is active
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->scholarship_status === 1;
    }

    /**
     * Check if scholarship is denied
     * @return bool
     */
    public function isDenied(): bool
    {
        return $this->scholarship_status === 2;
    }

    /**
     * Get human-readable approval status name
     * @return string
     */
    public function getStatusDisplayName(): string
    {
        return match ($this->approval_status) {
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'auto_approved' => 'Auto-Approved',
            'declined' => 'Declined',
            'conditional' => 'Conditional Approval',
            default => 'Unknown',
        };
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name']);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select(['id', 'name']);
    }

    public function scholarshipStatus()
    {
        return $this->belongsTo(ScholarshipStatus::class, 'scholarship_status_id')->select(['id', 'status', 'remarks', 'status_id']);
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
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function declinedBy()
    {
        return $this->belongsTo(User::class, 'declined_by');
    }

    public function resubmissionAllowedBy()
    {
        return $this->belongsTo(User::class, 'resubmission_allowed_by');
    }

    public function approvalHistory()
    {
        return $this->hasMany(ScholarshipApprovalHistory::class, 'scholarship_record_id', 'id');
    }

    // Status helper methods using configuration
    public function getApprovalStatusConfig()
    {
        return config('scholarship.approval_statuses')[$this->approval_status] ?? null;
    }

    public function getApprovalStatusLabel()
    {
        return $this->getApprovalStatusConfig()['label'] ?? ucfirst($this->approval_status);
    }

    public function getApprovalStatusColor()
    {
        return $this->getApprovalStatusConfig()['color'] ?? 'secondary';
    }

    public function getCompletionStatusConfig()
    {
        return config('scholarship.completion_statuses')[$this->completion_status] ?? null;
    }

    public function getCompletionStatusLabel()
    {
        return $this->getCompletionStatusConfig()['label'] ?? ucfirst($this->completion_status);
    }

    public function getCompletionStatusColor()
    {
        return $this->getCompletionStatusConfig()['color'] ?? 'secondary';
    }

    // Status checks
    public function isPending()
    {
        return $this->approval_status === 'pending';
    }

    public function isApproved()
    {
        return $this->approval_status === 'approved';
    }

    public function isDeclined()
    {
        return $this->approval_status === 'declined';
    }

    public function isConditional()
    {
        return $this->approval_status === 'conditional';
    }

    public function isResubmitted()
    {
        return $this->approval_status === 'resubmitted';
    }

    public function isCompleted()
    {
        return $this->completion_status === 'completed';
    }

    public function isDiscontinued()
    {
        return $this->completion_status === 'discontinued';
    }

    public function canBeModified()
    {
        return in_array($this->approval_status, ['pending', 'conditional', 'resubmitted']);
    }

    public function canBeResubmitted()
    {
        $maxResubmissions = config('scholarship.application_cycle_limits.max_cycles', 2);
        return $this->approval_status === 'declined'
            && $this->resubmission_count < $maxResubmissions
            && (!$this->resubmission_deadline || now()->lte($this->resubmission_deadline));
    }

    public function canApplyForNext()
    {
        return $this->isCompleted()
            && !$this->hasActiveNextApplication()
            && $this->meetsGradeRequirement()
            && $this->hasAvailableNextLevels();
    }

    public function hasActiveNextApplication()
    {
        return $this->nextApplications()
            ->whereIn('approval_status', ['pending', 'approved', 'conditional'])
            ->exists();
    }

    public function meetsGradeRequirement()
    {
        $minGrade = config('scholarship.application_cycle_limits.grade_requirement', 2.0);
        $completion = $this->completion;

        return $completion && $completion->final_grade <= $minGrade;
    }

    public function hasAvailableNextLevels()
    {
        return count($this->getAvailableNextLevels()) > 0;
    }

    public function getAvailableNextLevels()
    {
        // This would need to be implemented based on your program structure
        // For now, returning basic progression
        $currentLevel = $this->next_degree_level ?? 'undergraduate';
        $currentConfig = config('scholarship.degree_levels')[$currentLevel] ?? null;

        return $currentConfig ? $currentConfig['next_levels'] : [];
    }

    public function getApplicationHistory()
    {
        return ScholarshipRecord::where('profile_id', $this->profile_id)
            ->orderBy('application_cycle')
            ->with(['program', 'course', 'completion'])
            ->get();
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

    // Scopes for querying
    public function scopeByApprovalStatus($query, $status)
    {
        return $query->where('approval_status', $status);
    }

    public function scopeByCompletionStatus($query, $status)
    {
        return $query->where('completion_status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeDeclined($query)
    {
        return $query->where('approval_status', 'declined');
    }

    public function scopeActive($query)
    {
        return $query->where('completion_status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('completion_status', 'completed');
    }

    public function scopeRenewalApplications($query)
    {
        return $query->whereNotNull('previous_scholarship_id');
    }

    public function updateScholarshipStatus($status_id)
    {
        $status_remarks = '';
        if ($status_id == 0) {
            $status_remarks = 'pending for approval';
        }
        if ($status_id == 1) {
            $status_remarks = 'approved and ongoing';
        }
        if ($status_id == 2) {
            $status_remarks = 'grant completed';
        }
        if ($status_id == 3) {
            $status_remarks = 'suspended';
        }
        if ($status_id == 4) {
            $status_remarks = 'cancelled';
        }

        $this->scholarship_status = $status_id;
        $this->scholarship_status_remarks = $status_remarks;
        return $this->save();
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
