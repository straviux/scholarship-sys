<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'password',
        'profile_photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's profile photo URL.
     *
     * @return string|null
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if ($this->profile_photo) {
            return asset('storage/' . $this->profile_photo);
        }

        // Return a default avatar or initials-based avatar
        return null;
    }

    /**
     * Check if user has a profile photo.
     *
     * @return bool
     */
    public function hasProfilePhoto(): bool
    {
        return !empty($this->profile_photo);
    }

    /**
     * Get unread notifications count for this user.
     *
     * @return int
     */
    public function getUnreadNotificationsCount(): int
    {
        try {
            return \App\Models\SystemUpdate::where('is_active', true)
                ->where('is_global', true)
                ->whereDoesntHave('readByUsers', function ($query) {
                    $query->where('user_id', $this->id);
                })
                ->count();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting unread notifications count: ' . $e->getMessage());
            return 0;
        }
    }
}
