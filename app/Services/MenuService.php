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
        try {
            // Validate user
            if (!$user) {
                \Log::warning('getUserMenu called with null user');
                return [];
            }

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
                // Get user's roles - with error handling
                try {
                    $userRoles = $user->roles ? $user->roles->pluck('id')->toArray() : [];
                } catch (\Exception $e) {
                    \Log::warning('Error loading user roles: ' . $e->getMessage());
                    $userRoles = [];
                }

                // If user has no roles, return empty menu
                if (empty($userRoles)) {
                    return [];
                }

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
        } catch (\Exception $e) {
            \Log::error('Error in getUserMenu: ' . $e->getMessage(), [
                'user_id' => $user ? $user->id : null,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Build menu structure with nested children
     */
    private function buildMenuStructure($items): array
    {
        try {
            if (!$items) {
                return [];
            }

            $menu = [];

            foreach ($items as $item) {
                try {
                    // Skip invalid items
                    if (!$item || !isset($item->id)) {
                        continue;
                    }

                    $menuData = [
                        'id' => $item->id,
                        'name' => $item->name ?? 'Untitled',
                        'icon' => $item->icon ?? 'pi pi-chevron-right',
                        'route' => $item->route ?? null,
                        'order' => $item->order ?? 0,
                        'is_group' => $item->is_group ?? false,
                    ];

                    // Load children if they exist
                    if ($item->children && $item->children->count() > 0) {
                        $menuData['children'] = $item->children->map(function ($child) {
                            return [
                                'id' => $child->id ?? null,
                                'name' => $child->name ?? 'Untitled',
                                'icon' => $child->icon ?? 'pi pi-chevron-right',
                                'route' => $child->route ?? null,
                                'order' => $child->order ?? 0,
                                'is_group' => $child->is_group ?? false,
                            ];
                        })->filter(function ($child) {
                            return $child['id'] !== null; // Filter out invalid children
                        })->sortBy('order')->values()->toArray();
                    }

                    $menu[] = $menuData;
                } catch (\Exception $e) {
                    \Log::warning('Error processing menu item: ' . $e->getMessage(), [
                        'item_id' => $item ? $item->id : null
                    ]);
                    continue;
                }
            }

            return $menu;
        } catch (\Exception $e) {
            \Log::error('Error building menu structure: ' . $e->getMessage());
            return [];
        }
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
        try {
            if (!$user) {
                \Log::warning('getSidebarMenu called with null user');
                return [];
            }

            $menu = $this->getUserMenu($user);

            if (empty($menu)) {
                return [];
            }

            return array_filter($menu, function ($item) {
                // Include items that have:
                // 1. A route (regular menu items)
                // 2. Children (groups with items)
                // 3. Is marked as a group (empty groups should still show)
                return !empty($item['route']) || !empty($item['children']) || ($item['is_group'] ?? false);
            });
        } catch (\Exception $e) {
            \Log::error('Error in getSidebarMenu: ' . $e->getMessage(), [
                'user_id' => $user ? $user->id : null,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
}
