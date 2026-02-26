<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ReturnOfService extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'return_of_service';

    protected $fillable = [
        'batch_id',
        'profile_id',
        'scholarship_record_id',
        'years_of_service',
        'service_start_date',
        'service_end_date',
        'completion_status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'graduation_date' => 'date',
        'service_start_date' => 'date',
        'service_end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function batch()
    {
        return $this->belongsTo(ReturnOfServiceBatch::class, 'batch_id', 'id');
    }

    public function scholarshipRecord()
    {
        return $this->belongsTo(ScholarshipRecord::class, 'scholarship_record_id', 'id');
    }

    public function profile()
    {
        return $this->belongsTo(ScholarshipProfile::class, 'profile_id', 'profile_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name']);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select(['id', 'name']);
    }

    /**
     * Scopes for filtering
     */
    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('completion_status', 'completed');
    }

    public function scopeOngoing($query)
    {
        return $query->where('completion_status', 'ongoing');
    }

    public function scopeSuspended($query)
    {
        return $query->where('completion_status', 'suspended');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('graduation_date', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Boot method for created_by and updated_by
     */
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
