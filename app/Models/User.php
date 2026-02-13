<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
        'email',
        'phone',
        'password',
        'office_designation',
        'profile_photo',
        'upload_token',
        'upload_token_expires_at',
        'preferences',
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
            'upload_token_expires_at' => 'datetime',
            'preferences' => 'array',
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

    /**
     * Generate a new upload token for mobile profile photo upload.
     *
     * @param int $expiresInMinutes Token expiration time in minutes (default: 43200 = 30 days)
     * @return string The generated token
     */
    public function generateUploadToken($expiresInMinutes = 43200): string
    {
        $this->upload_token = Str::random(64);
        $this->upload_token_expires_at = now()->addMinutes($expiresInMinutes);
        $this->save();

        return $this->upload_token;
    }

    /**
     * Get the mobile upload URL for this user's profile photo.
     *
     * @return string
     */
    public function getMobileUploadUrl(): string
    {
        // Generate token if it doesn't exist or has expired
        if (!$this->upload_token || $this->upload_token_expires_at < now()) {
            $this->generateUploadToken();
        }

        $baseUrl = request()->getSchemeAndHttpHost() ?: config('app.url');
        return $baseUrl . '/mobile/upload/profile/' . $this->upload_token;
    }

    /**
     * Generate QR code for mobile profile photo upload.
     *
     * @param int $size QR code size in pixels
     * @return string SVG QR code
     */
    public function getUploadQrCode($size = 200): string
    {
        $url = $this->getMobileUploadUrl();
        $qrCode = QrCode::size($size)->generate($url);

        return (string) $qrCode;
    }

    /**
     * Generate QR code as base64 data URI for mobile profile photo upload.
     *
     * @param int $size QR code size in pixels
     * @return string Base64 data URI
     */
    public function getUploadQrCodeDataUri($size = 200): string
    {
        $url = $this->getMobileUploadUrl();
        $qrCode = QrCode::size($size)->format('png')->generate($url);

        return 'data:image/png;base64,' . base64_encode($qrCode);
    }

    /**
     * Get the user activities for this user.
     */
    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    /**
     * Check if user has a specific permission through their role.
     * 
     * Usage:
     *   $user->hasPermission('users.create')        // true if user's role has this permission
     *   $user->hasPermission('applicants.edit')     // true if user's role has this permission
     *
     * @param string $permission The permission name to check
     * @return bool
     */
    public function hasPermission($permission): bool
    {
        // Check if user has the permission through their role
        return $this->roles()
            ->whereHas('permissions', fn($query) => $query->where('name', $permission))
            ->exists();
    }

    /**
     * Check if user has any of the specified roles.
     * 
     * Usage:
     *   $user->hasAnyRole(['admin', 'manager'])     // true if user has any of these roles
     *   $user->hasAnyRole('admin', 'manager')       // true if user has any of these roles
     *
     * @param array|string ...$roles The role names to check
     * @return bool
     */
    public function hasAnyRole(...$roles): bool
    {
        // Flatten the array if the first argument is an array
        $rolesToCheck = (is_array($roles[0]) ?? false) ? $roles[0] : $roles;

        return $this->roles()
            ->whereIn('name', $rolesToCheck)
            ->exists();
    }
}
