<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantListEntry extends Model
{
    use HasFactory;

    /** Shared pipeline lists — a profile belongs to at most one, and is hidden from the main tab. */
    public const SHARED_LISTS = ['waiting', 'interview', 'endorsed'];

    /** Per-account list — additive, does not hide the profile from the main tab. */
    public const PERSONAL = 'personal';

    public const ALL_LISTS = ['waiting', 'interview', 'endorsed', self::PERSONAL];

    protected $fillable = [
        'profile_id',
        'list_type',
        'user_id',
        'added_by',
        'note',
    ];

    public function profile()
    {
        return $this->belongsTo(ScholarshipProfile::class, 'profile_id', 'profile_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function scopeShared($query)
    {
        return $query->whereIn('list_type', self::SHARED_LISTS);
    }

    public function scopePersonalFor($query, ?int $userId)
    {
        return $query->where('list_type', self::PERSONAL)->where('user_id', $userId);
    }

    public static function isSharedList(string $listType): bool
    {
        return in_array($listType, self::SHARED_LISTS, true);
    }
}
