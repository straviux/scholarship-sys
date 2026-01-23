<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangeHistory extends Model
{
    use HasFactory;

    protected $table = 'change_history';

    protected $fillable = [
        'activity_log_id',
        'field_name',
        'field_label',
        'old_value',
        'new_value',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the activity log associated with this change
     */
    public function activityLog()
    {
        return $this->belongsTo(ActivityLog::class);
    }
}
