<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class RoleMenuController extends Controller
{
    /**
     * Check if user is an administrator
     */
    private function checkAdmin()
    {
        if (!auth()->check() || !auth()->user()->hasRole('administrator')) {
            abort(403, 'Unauthorized access');
        }
    }

    /**
     * Display role-menu management interface
     */
    public function index()
    {
        $this->checkAdmin();

        $roles = Role::orderBy('name')->get();
        $menuItems = MenuItem::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => function ($query) {
                $query->where('is_active', true)->orderBy('order');
            }])
            ->orderBy('order')
            ->get();

        return Inertia::render('Admin/RoleMenuManagement/Index', [
            'roles' => $roles,
            'menuItems' => $menuItems,
        ]);
    }

    /**
     * Get menus for a specific role
     */
    public function getRoleMenus(Role $role)
    {
        $this->checkAdmin();

        $menuIds = $role->menuItems()
            ->pluck('menu_items.id')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $menuIds,
        ]);
    }

    /**
     * Assign menus to a role and sync permissions
     */
    public function assignMenus(Request $request, Role $role)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'menu_ids' => 'array',
            'menu_ids.*' => 'exists:menu_items,id',
        ]);

        $menuIds = $validated['menu_ids'] ?? [];

        // Get menu items with their permissions
        $menuItems = MenuItem::whereIn('id', $menuIds)
            ->whereNotNull('permission')
            ->get();

        // Collect all permissions from the assigned menus
        $permissions = [];
        foreach ($menuItems as $menuItem) {
            if ($menuItem->permission) {
                // Ensure permission exists
                $permission = Permission::firstOrCreate(['name' => $menuItem->permission]);
                $permissions[] = $permission->name;
            }
        }

        // Sync menus to role
        $role->menuItems()->sync($menuIds);

        // Sync permissions to role (keep existing permissions, add menu permissions)
        $existingPermissions = $role->permissions->pluck('name')->toArray();
        $allPermissions = array_unique(array_merge($existingPermissions, $permissions));
        $role->syncPermissions($allPermissions);

        return response()->json([
            'success' => true,
            'message' => 'Menus and permissions assigned successfully',
            'permissions_added' => $permissions,
        ]);
    }

    /**
     * Update menu order for a role
     */
    public function updateOrder(Request $request, Role $role)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'menu_orders' => 'required|array',
            'menu_orders.*.id' => 'required|exists:menu_items,id',
            'menu_orders.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['menu_orders'] as $menuOrder) {
            $role->menuItems()->updateExistingPivot(
                $menuOrder['id'],
                ['order' => $menuOrder['order']]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Menu order updated successfully',
        ]);
    }
}
