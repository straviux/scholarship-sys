<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(CreatePermissionRequest $request)
    {
        $permission = Permission::create($request->validated());

        // Log permission creation
        ActivityLogService::logRecordCreated(
            profileId: null,
            recordData: ['name' => $permission->name],
            remarks: "Created permission: {$permission->name}"
        );

        // Return JSON if AJAX request
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'permission' => new PermissionResource($permission),
                'message' => 'Permission created successfully'
            ]);
        }

        return to_route('access-control.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CreatePermissionRequest $request, Permission $permission)
    {
        $oldData = $permission->getAttributes();
        $permission->update($request->validated());

        // Log permission update
        ActivityLogService::logRecordUpdated(
            profileId: null,
            oldData: $oldData,
            newData: $permission->fresh()->getAttributes(),
            remarks: "Updated permission: {$permission->name}"
        );

        // Return JSON if AJAX request
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'permission' => new PermissionResource($permission),
                'message' => 'Permission updated successfully'
            ]);
        }

        return to_route('access-control.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Permission $permission)
    {
        try {
            $permissionData = $permission->getAttributes();
            // ✅ IMPORTANT: Before deleting, detach permission from all roles
            // (Direct user permissions are no longer supported)
            if ($permission->roles()->exists()) {
                $permission->roles()->detach();
            }

            // Now safe to delete
            $permission->delete();

            // Log permission deletion
            ActivityLogService::logRecordDeleted(
                profileId: null,
                recordData: $permissionData,
                remarks: "Deleted permission: {$permissionData['name']}"
            );

            // Return JSON if AJAX request
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Permission deleted successfully'
                ]);
            }

            return back()->with('success', 'Permission deleted successfully');
        } catch (\Exception $e) {
            Log::error('Permission deletion failed', [
                'permission_id' => $permission->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete permission: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to delete permission: ' . $e->getMessage());
        }
    }

    /**
     * Cleanup permissions - fix orphaned records, duplicates, and invalid pivot table entries
     */
    public function cleanup(Request $request)
    {
        try {
            $results = [];

            // 1. Remove orphaned entries in role_has_permissions
            $deletedOrphanedRole = DB::table('role_has_permissions')
                ->leftJoin('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
                ->whereNull('permissions.id')
                ->delete();

            if ($deletedOrphanedRole > 0) {
                $results['orphaned_role_permissions'] = $deletedOrphanedRole;
            }

            // 2. Remove duplicate entries in role_has_permissions
            $dupRolePerms = DB::select(
                "SELECT role_id, permission_id FROM role_has_permissions 
                GROUP BY role_id, permission_id 
                HAVING COUNT(*) > 1"
            );

            $dupRoleCount = 0;
            foreach ($dupRolePerms as $dup) {
                $dupRoleCount += DB::table('role_has_permissions')
                    ->where('role_id', $dup->role_id)
                    ->where('permission_id', $dup->permission_id)
                    ->skip(1)
                    ->delete();
            }

            if ($dupRoleCount > 0) {
                $results['duplicate_role_permissions'] = $dupRoleCount;
            }

            // 3. Clear permission cache
            \Spatie\Permission\PermissionRegistrar::class;
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return response()->json([
                'success' => true,
                'message' => 'Permissions cleanup completed successfully! (Using role-based permissions only)',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Permission cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Cleanup failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
