<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class AcademicEnrollmentTerm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_enrollment_id',
        'year_level',
        'academic_year',
        'term',
        'unified_status',
        'date_filed',
        'date_approved',
        'grant_provision',
        'remarks',
        'upload_token',
        'upload_token_expires_at',
        'yakap_category',
        'yakap_location',
        'academic_potential',
        'financial_need_level',
        'communication_skills',
        'recommendation',
        'interview_remarks',
        'interviewed_by',
        'interviewed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_filed' => 'date',
        'date_approved' => 'date',
        'upload_token_expires_at' => 'datetime',
        'interviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            $userId = Auth::id();

            if ($userId && !$model->created_by) {
                $model->created_by = $userId;
            }

            if ($userId) {
                $model->updated_by = $userId;
            }
        });

        static::updating(function (self $model) {
            $userId = Auth::id();

            if ($userId) {
                $model->updated_by = $userId;
            }
        });
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(AcademicEnrollment::class, 'academic_enrollment_id');
    }

    public function recordMaps(): HasMany
    {
        return $this->hasMany(AcademicEnrollmentTermRecordMap::class)
            ->orderByDesc('is_primary')
            ->orderByDesc('id');
    }

    public function primaryRecordMap(): HasOne
    {
        return $this->hasOne(AcademicEnrollmentTermRecordMap::class)
            ->where('is_primary', true);
    }
}