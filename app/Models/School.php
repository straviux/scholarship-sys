<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class School extends Model
{
    protected $fillable = [
        'name',
        'shortname',
        'campus',
        'address',
        'remarks',
        'is_active',
        'start_date',
        'end_date',
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
