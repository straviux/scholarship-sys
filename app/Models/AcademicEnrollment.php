<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class AcademicEnrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'profile_id',
        'program_id',
        'school_id',
        'course_id',
        'graduation_date',
        'graduation_remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'graduation_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'is_graduated',
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

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ScholarshipProfile::class, 'profile_id', 'profile_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ScholarshipProgram::class, 'program_id')->select(['id', 'name']);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id')->select(['id', 'name']);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id')->select(['id', 'name', 'scholarship_program_id']);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(AcademicEnrollmentTerm::class)
            ->orderByRaw('COALESCE(date_approved, date_filed, created_at) DESC')
            ->orderByDesc('id');
    }

    public function getIsGraduatedAttribute(): bool
    {
        return !is_null($this->graduation_date);
    }
}