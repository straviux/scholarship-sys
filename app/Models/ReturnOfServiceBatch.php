<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ReturnOfServiceBatch extends Model
{
    use SoftDeletes;

    protected $table = 'return_of_service_batches';

    protected $fillable = [
        'batch_name',
        'description',
        'exam_date_from',
        'exam_date_to',
        'result_date',
        'result_date_from',
        'result_date_to',
        'course_id',
        'total_scholars',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'exam_date_from' => 'date',
        'exam_date_to' => 'date',
        'result_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }

    public function rosRecords()
    {
        return $this->hasMany(ReturnOfService::class, 'batch_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('batch_name', 'asc');
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('batch_name');
    }

    /**
     * Boot method for automatic user tracking
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }
}
