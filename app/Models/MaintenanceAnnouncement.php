<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MaintenanceAnnouncement extends Model
{
    protected $fillable = [
        'is_active',
        'title',
        'message',
        'start_time',
        'end_time',
        'type',
        'allow_admin_access',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_admin_access' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the active maintenance announcement
     */
    public static function getActive()
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Check if system is under maintenance
     */
    public static function isUnderMaintenance()
    {
        $maintenance = static::getActive();

        if (!$maintenance) {
            return false;
        }

        $now = Carbon::now();

        // If start_time and end_time are set, check if current time is in range
        if ($maintenance->start_time && $maintenance->end_time) {
            return $now->between($maintenance->start_time, $maintenance->end_time);
        }

        // If only is_active is true, consider it as maintenance
        return true;
    }

    /**
     * Get countdown data
     */
    public function getCountdownData()
    {
        $now = Carbon::now();

        if (!$this->start_time) {
            return null;
        }

        // If maintenance is active
        if ($now->gte($this->start_time) && (!$this->end_time || $now->lt($this->end_time))) {
            $remainingSeconds = $this->end_time ? $this->end_time->diffInSeconds($now) : null;

            return [
                'status' => 'active',
                'message' => 'System is under maintenance',
                'end_time' => $this->end_time ? $this->end_time->toIso8601String() : null,
                'seconds_remaining' => $remainingSeconds,
                'duration_minutes' => $this->end_time ? $this->start_time->diffInMinutes($this->end_time) : null,
            ];
        }

        // If maintenance is upcoming
        if ($now->lt($this->start_time)) {
            $diff = $this->start_time->diff($now);

            return [
                'status' => 'upcoming',
                'message' => 'Maintenance starts in',
                'start_time' => $this->start_time->toIso8601String(),
                'seconds_remaining' => $this->start_time->diffInSeconds($now),
            ];
        }

        // If maintenance is completed
        if ($this->end_time && $now->gt($this->end_time)) {
            return [
                'status' => 'completed',
                'message' => 'Maintenance completed',
            ];
        }

        return null;
    }
}
