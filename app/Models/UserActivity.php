<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns this activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a user activity
     */
    public static function log($userId, $action, $description = null, $data = null)
    {
        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data,
        ]);
    }

    /**
     * Get action label for display
     */
    public function getActionLabel()
    {
        $labels = [
            'login' => 'User Login',
            'logout' => 'User Logout',
            'create_voucher' => 'Created Voucher',
            'update_voucher' => 'Updated Voucher',
            'delete_voucher' => 'Deleted Voucher',
            'create_applicant' => 'Encoded New Applicant',
            'update_applicant' => 'Updated Applicant',
            'create_scholar' => 'Created Scholar Record',
            'update_scholar' => 'Updated Scholar',
            'update_profile' => 'Updated Profile',
            'change_password' => 'Changed Password',
            'update_settings' => 'Updated Settings',
        ];

        return $labels[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }
}
