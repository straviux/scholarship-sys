<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class PermissionManagementController extends Controller
{
    public function updateRolePermissions(Request $request)
    {
        $request->validate([
            'role_name' => 'required|string|exists:roles,name',
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::findByName($request->role_name);
        $oldPermissions = $role->permissions->pluck('name')->toArray();
        $role->syncPermissions($request->permissions);
        $newPermissions = $role->permissions->pluck('name')->toArray();

        // Log role permissions update
        ActivityLogService::logRecordUpdated(
            profileId: null,
            oldData: ['permissions' => $oldPermissions],
            newData: ['permissions' => $newPermissions],
            remarks: "Updated permissions for role: {$role->name}"
        );

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // If the current user is affected by this change, refresh their session permissions
        if ($request->user()->hasRole($request->role_name)) {
            Auth::setUser($request->user()->refresh());
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
            Auth::setUser($request->user()->refresh());
        }

        return back()->with('success', 'Permission updated successfully');
    }
}
