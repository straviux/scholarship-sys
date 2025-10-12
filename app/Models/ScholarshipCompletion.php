<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'scholarship_record_id',
        'profile_id',
        'program_id',
        'course_id',
        'school_id',
        'completion_date',
        'final_grade',
        'graduation_date',
        'honors',
        'completion_certificate_path',
        'completion_remarks',
        'verified_by',
        'verified_at'
    ];

    protected $casts = [
        'completion_date' => 'date',
        'graduation_date' => 'date',
        'verified_at' => 'datetime',
        'final_grade' => 'decimal:2'
    ];

    public function scholarshipRecord()
    {
        return $this->belongsTo(ScholarshipRecord::class, 'scholarship_record_id', 'id');
    }

    public function profile()
    {
        return $this->belongsTo(ScholarshipProfile::class, 'profile_id', 'profile_id');
    }

    public function program()
    {
        return $this->belongsTo(ScholarshipProgram::class, 'program_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified()
    {
        return !is_null($this->verified_at);
    }

    public function getGradeLevelDescription()
    {
        if ($this->final_grade <= 1.25) return 'Summa Cum Laude';
        if ($this->final_grade <= 1.45) return 'Magna Cum Laude';
        if ($this->final_grade <= 1.75) return 'Cum Laude';
        if ($this->final_grade <= 2.0) return 'Very Good';
        if ($this->final_grade <= 3.0) return 'Good';
        return 'Satisfactory';
    }
}
