<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Scholar extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicantFactory> */
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'course_id',
        'scholarship_program_id',
        'term',
        'year_level',
        'academic_year',
        'school_name',
        'company_name',
        'start_date',
        'end_date',
        'remarks',
        'academic_status',
        'is_active',
        'created_by',
        'updated_by',
        'scholarship_status_id',
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

    public function updateScholarshipStatus($status_id)
    {
        $this->scholarship_status_id = $status_id;
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
