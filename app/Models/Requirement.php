<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Requirement extends Model
{

    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'name',
        'description',
        'remarks',
        'is_active',
        'created_by',
        'updated_by',
    ];



    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name']);
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select(['id', 'name']);
    }

    public function programs()
    {
        return $this->belongsToMany(ScholarshipProgram::class, 'program_requirements_detail', 'program_id', 'requirement_id');
    }

    public function scholarshipRecords()
    {
        return $this->hasMany(ScholarshipRecord::class);
    }

    public static function boot()
    {
        parent::boot();
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
    }
}
