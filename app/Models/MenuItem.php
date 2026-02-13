<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role;

class MenuItem extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'route',
        'permission',
        'category',
        'order',
        'parent_id',
        'is_active',
        'is_group',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_group' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get child menu items
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order');
    }

    /**
     * Get parent menu item
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * Get roles that have access to this menu item
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menu_item')
            ->withPivot('order')
            ->withTimestamps();
    }

    /**
     * Get all menu items organized by category
     */
    public static function getActiveMenusByCategory($category = null)
    {
        $query = self::where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('category')
            ->orderBy('order');

        if ($category) {
            $query->where('category', $category);
        }

        return $query->with('children')->get();
    }

    /**
     * Get menus filtered by user permissions
     */
    public static function getMenusForUser($user)
    {
        return self::where('is_active', true)
            ->whereNull('parent_id')
            ->get()
            ->filter(function ($menu) use ($user) {
                // If no permission required, show it
                if (!$menu->permission) {
                    return true;
                }
                // Check if user has the required permission
                return $user->hasPermissionTo($menu->permission);
            })
            ->map(function ($menu) use ($user) {
                $menu->children = $menu->children()
                    ->get()
                    ->filter(function ($child) use ($user) {
                        if (!$child->permission) {
                            return true;
                        }
                        return $user->hasPermissionTo($child->permission);
                    })
                    ->values();
                return $menu;
            })
            ->values();
    }
}
