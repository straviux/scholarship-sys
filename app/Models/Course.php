<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Course extends Model
{
    protected $fillable = [
        'name',
        'shortname',
        'field_of_study',
        'description',
        'remarks',
        'is_active',
        'start_date',
        'end_date',
        'created_by',
        'updated_by',
        'scholarship_program_id'
    ];



    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scholarshipProgram()
    {
        return $this->belongsTo(ScholarshipProgram::class, 'scholarship_program_id');
    }

    public function scholars()
    {
        return $this->hasMany(ScholarshipRecord::class);
    }


    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $user = Auth::user();
            $model->created_by = $user?->id;
            $model->updated_by = $user?->id;
        });
        static::updating(function ($model) {
            $user = Auth::user();
            $model->updated_by = $user?->id;
        });
    }
}
