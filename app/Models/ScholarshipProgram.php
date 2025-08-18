<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ScholarshipProgram extends Model
{
    protected $fillable = [
        'name',
        'shortname',
        'description',
        'remarks',
        'is_active',
        'start_date',
        'end_date',
        'created_by',
        'updated_by',
    ];


    public function courses()
    {
        return $this->hasMany(Course::class, 'scholarship_program_id');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name']);
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getIsActiveAttribute($value)
    {
        return $value ? 'Active' : 'Inactive';
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
