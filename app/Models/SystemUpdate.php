<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class SystemUpdate extends Model
{
    protected $fillable = [
        'title',
        'content',
        'type',
        'priority',
        'is_global',
        'target_roles',
        'is_active',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'is_global' => 'boolean',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function readByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'system_update_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where('is_global', true)
                ->orWhere(function ($subQ) use ($user) {
                    $subQ->where('is_global', false)
                        ->where(function ($roleQ) use ($user) {
                            foreach ($user->getRoleNames() as $role) {
                                $roleQ->orWhereJsonContains('target_roles', $role);
                            }
                        });
                });
        });
    }

    public function isReadBy(User $user): bool
    {
        return $this->readByUsers()->where('user_id', $user->id)->exists();
    }
}
