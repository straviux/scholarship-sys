<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
        'scholarship_status_date',
        'is_active',
        'created_by',
        'updated_by',
        'date_filed',
        'date_approved'
    ];


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
        return $this->hasOne(Course::class, 'id', 'course_id')->select('id', 'name', 'shortname');
    }

    public function school()
    {
        return $this->hasOne(School::class, 'id', 'school_id')->select('id', 'name', 'shortname');
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
        )->with('requirements')->select('scholarship_programs.name', 'scholarship_programs.shortname', 'scholarship_programs.id');
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
        return $this->belongsTo(ScholarshipProfile::class, 'profile_id')->select('profile_id', 'first_name', 'last_name', 'middle_name', 'extension_name', 'gender');
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

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $user = Auth::user();
            $model->created_by = $user->id;
            $model->updated_by = $user->id;
        });
        static::updating(function ($model) {
            $user = Auth::user();
            $model->updated_by = $user->id;
        });
    }
}
