<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateRoleRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRoleRequest $request): RedirectResponse
    {
        $role =  Role::create(['name' => $request->name]);
        if ($request->has('permissions')) {
            $role->syncPermissions($request->input('permissions.*.name'));
        }

        // Log role creation
        ActivityLogService::logRecordCreated(
            profileId: null,
            recordData: [
                'name' => $role->name,
                'permissions_count' => $role->permissions->count()
            ],
            remarks: "Created role: {$role->name}"
        );

        return to_route('access-control.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CreateRoleRequest $request, Role $role): RedirectResponse
    {
        // Prevent renaming administrator role to maintain system integrity
        if ($role->name === 'administrator' && $request->name !== 'administrator') {
            return back()->withErrors(['name' => 'Administrator role name cannot be changed.']);
        }

        $oldData = $role->getAttributes();
        // $role = Role::findById($id);
        $role->update([
            'name' => $request->name
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->input('permissions.*.name'));
        }

        // Log role update
        ActivityLogService::logRecordUpdated(
            profileId: null,
            oldData: $oldData,
            newData: $role->fresh()->getAttributes(),
            remarks: "Updated role: {$role->name}"
        );

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // Prevent deletion of administrator role to maintain system integrity
        if ($role->name === 'administrator') {
            return back()->withErrors(['error' => 'Administrator role cannot be deleted.']);
        }

        $roleData = $role->getAttributes();
        // $role = Role::findById($id);
        $role->delete();

        // Log role deletion
        ActivityLogService::logRecordDeleted(
            profileId: null,
            recordData: $roleData,
            remarks: "Deleted role: {$roleData['name']}"
        );

        return back();
    }

    /**
     * Attach a permission to a role (inline assignment)
     */
    public function attachPermission(Request $request)
    {
        $role = Role::findOrFail($request->role_id);
        $permission = Permission::findOrFail($request->permission_id);

        $role->givePermissionTo($permission);

        return response()->json(['message' => 'Permission assigned successfully.']);
    }

    /**
     * Detach a permission from a role (inline removal)
     */
    public function detachPermission(Role $role, Permission $permission)
    {
        $role->revokePermissionTo($permission);

        return response()->json(['message' => 'Permission revoked successfully.']);
    }
}
