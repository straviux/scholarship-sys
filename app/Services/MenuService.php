<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;

class MenuService
{
    private const CACHE_TTL = 3600; // 1 hour
    /**
     * Get all active menu items organized by category
     */
    public function getMainMenu(): array
    {
        return Cache::remember('menu.main', self::CACHE_TTL, function () {
            $menuItems = MenuItem::where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('order')
                ->with(['children' => function ($query) {
                    $query->where('is_active', true)->orderBy('order');
                }])
                ->get();

            return $this->buildMenuStructure($menuItems);
        });
    }

    /**
     * Get menu items for a specific category
     */
    public function getMenuByCategory(string $category): array
    {
        $menuItems = MenuItem::where('is_active', true)
            ->where('category', $category)
            ->whereNull('parent_id')
            ->orderBy('order')
            ->with('children')
            ->get();

        return $this->buildMenuStructure($menuItems);
    }

    /**
     * Get all menu items with their children
     */
    public function getAllMenuItems(): EloquentCollection
    {
        return Cache::remember('menu.all', self::CACHE_TTL, function () {
            return MenuItem::where('is_active', true)
                ->orderBy('order')
                ->with(['children' => function ($query) {
                    $query->where('is_active', true)->orderBy('order');
                }])
                ->get();
        });
    }

    /**
     * Get menu items for a specific user based on permissions
     */
    public function getUserMenu($user): array
    {
        // If user is administrator, show all menus
        if ($user->hasRole('administrator')) {
            $menuItems = MenuItem::where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('order')
                ->with(['children' => function ($query) {
                    $query->where('is_active', true)->orderBy('order');
                }])
                ->get();
        } else {
            // Get user's roles
            $userRoles = $user->roles->pluck('id')->toArray();

            // Get all active parent menu items with their children that are assigned to user's roles
            $menuItems = MenuItem::where('is_active', true)
                ->whereNull('parent_id')
                ->whereHas('roles', function ($query) use ($userRoles) {
                    $query->whereIn('roles.id', $userRoles);
                })
                ->orderBy('order')
                ->with(['children' => function ($query) use ($userRoles) {
                    $query->where('is_active', true)
                        ->whereHas('roles', function ($q) use ($userRoles) {
                            $q->whereIn('roles.id', $userRoles);
                        })
                        ->orderBy('order');
                }])
                ->get();
        }

        return $this->buildMenuStructure($menuItems);
    }

    /**
     * Build menu structure with nested children
     */
    private function buildMenuStructure($items): array
    {
        $menu = [];

        foreach ($items as $item) {
            $menuData = [
                'id' => $item->id,
                'name' => $item->name,
                'icon' => $item->icon,
                'route' => $item->route,
                'order' => $item->order,
                'is_group' => $item->is_group ?? false,
            ];

            // Load children if they exist
            if ($item->children) {
                $menuData['children'] = $item->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'icon' => $child->icon,
                        'route' => $child->route,
                        'order' => $child->order,
                        'is_group' => $child->is_group ?? false,
                    ];
                })->sortBy('order')->values()->toArray();
            }

            $menu[] = $menuData;
        }

        return $menu;
    }

    /**
     * Get a specific menu item
     */
    public function getMenuItem(int $id): ?MenuItem
    {
        return MenuItem::find($id);
    }

    /**
     * Check if a menu item is accessible for a user
     */
    public function isAccessible($user, MenuItem $item): bool
    {
        if (!$item->is_active) {
            return false;
        }

        if (!$item->permission) {
            return true;
        }

        return $user->can($item->permission);
    }

    /**
     * Get breadcrumb items for a given route
     */
    public function getBreadcrumbs(string $route): array
    {
        $breadcrumbs = [];

        $menuItem = MenuItem::where('route', $route)->first();

        if (!$menuItem) {
            return $breadcrumbs;
        }

        // If it's a child, add the parent
        if ($menuItem->parent_id) {
            $parent = MenuItem::find($menuItem->parent_id);
            if ($parent) {
                $breadcrumbs[] = [
                    'name' => $parent->name,
                    'route' => $parent->route,
                ];
            }
        }

        // Add the current item
        $breadcrumbs[] = [
            'name' => $menuItem->name,
            'route' => $menuItem->route,
        ];

        return $breadcrumbs;
    }

    /**
     * Update menu item visibility
     */
    public function toggleMenuItem(int $id): bool
    {
        $item = MenuItem::find($id);
        if (!$item) {
            return false;
        }

        $item->is_active = !$item->is_active;
        return $item->save();
    }

    /**
     * Get sidebar menu for current user
     */
    public function getSidebarMenu($user): array
    {
        $menu = $this->getUserMenu($user);

        return array_filter($menu, function ($item) {
            // Include items that have:
            // 1. A route (regular menu items)
            // 2. Children (groups with items)
            // 3. Is marked as a group (empty groups should still show)
            return !empty($item['route']) || !empty($item['children']) || ($item['is_group'] ?? false);
        });
    }
}
