<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'user_id',
        'activity_type',
        'action',
        'description',
        'old_value',
        'new_value',
        'details',
        'remarks',
        'snapshot_before',
        'snapshot_after',
        'performed_at',
        'is_viewed',
        'viewed_at'
    ];

    protected $casts = [
        'details' => 'array',
        'snapshot_before' => 'array',
        'snapshot_after' => 'array',
        'performed_at' => 'datetime',
        'viewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the profile associated with this activity
     */
    public function profile()
    {
        return $this->belongsTo(ScholarshipProfile::class, 'profile_id', 'profile_id');
    }

    /**
     * Get the user who performed the activity
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get changes made in this activity
     */
    public function changes()
    {
        return $this->hasMany(ChangeHistory::class);
    }

    /**
     * Scope to get unviewed activities for a user
     */
    public function scopeUnviewedForUser($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->where('is_viewed', false);
    }

    /**
     * Mark activity as viewed
     */
    public function markAsViewed()
    {
        $this->update([
            'is_viewed' => true,
            'viewed_at' => now()
        ]);
        return $this;
    }

    /**
     * Log an activity with snapshot
     */
    public static function logActivity(
        $profileId,
        $userId,
        $activityType,
        $action,
        $description = null,
        $oldValue = null,
        $newValue = null,
        $details = [],
        $remarks = null,
        $snapshotBefore = null,
        $snapshotAfter = null,
        $performedAt = null
    ) {
        return static::create([
            'profile_id' => $profileId,
            'user_id' => $userId,
            'activity_type' => $activityType,
            'action' => $action,
            'description' => $description,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'details' => $details,
            'remarks' => $remarks,
            'snapshot_before' => $snapshotBefore,
            'snapshot_after' => $snapshotAfter,
            'performed_at' => $performedAt ?? now()
        ]);
    }
}
