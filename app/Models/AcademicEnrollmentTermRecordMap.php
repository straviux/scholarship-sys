<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicEnrollmentTermRecordMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_enrollment_term_id',
        'scholarship_record_id',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function academicEnrollmentTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicEnrollmentTerm::class, 'academic_enrollment_term_id');
    }

    public function scholarshipRecord(): BelongsTo
    {
        return $this->belongsTo(ScholarshipRecord::class, 'scholarship_record_id');
    }
}