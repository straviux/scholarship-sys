<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionManagementController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();

        // Group permissions by module (extract module from permission name before the dot)
        $allPermissions = Permission::all();
        $groupedPermissions = $allPermissions->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);
            return $parts[0] ?? 'other';
        });

        // Get all permissions for each role
        $rolePermissions = [];
        foreach ($roles as $role) {
            $rolePermissions[$role->name] = $role->permissions->pluck('name')->toArray();
        }

        return Inertia::render('Administrator/PermissionManagement', [
            'roles' => $roles,
            'groupedPermissions' => $groupedPermissions,
            'rolePermissions' => $rolePermissions,
        ]);
    }

    public function updateRolePermissions(Request $request)
    {
        $request->validate([
            'role_name' => 'required|string|exists:roles,name',
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::findByName($request->role_name);
        $role->syncPermissions($request->permissions);

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // If the current user is affected by this change, refresh their session permissions
        if ($request->user()->hasRole($request->role_name)) {
            auth()->setUser(auth()->user()->refresh());
        }

        return back()->with('success', 'Permissions updated successfully for ' . $role->name);
    }

    public function togglePermission(Request $request)
    {
        $request->validate([
            'role_name' => 'required|string|exists:roles,name',
            'permission_name' => 'required|string|exists:permissions,name',
            'grant' => 'required|boolean',
        ]);

        $role = Role::findByName($request->role_name);

        if ($request->grant) {
            $role->givePermissionTo($request->permission_name);
        } else {
            $role->revokePermissionTo($request->permission_name);
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // If the current user is affected by this change, refresh their session permissions
        if ($request->user()->hasRole($request->role_name)) {
            auth()->setUser(auth()->user()->refresh());
        }

        return back()->with('success', 'Permission updated successfully');
    }
}
