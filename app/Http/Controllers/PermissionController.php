<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Permissions/PermissionIndex', [
            'permissions' => PermissionResource::collection(Permission::all())
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Permissions/Create');
    }

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

        return to_route('permissions.index');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission): Response
    {
        return Inertia::render('Admin/Permissions/Edit', [
            'permission' => new PermissionResource($permission)
        ]);
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

        return to_route('permissions.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Permission $permission)
    {
        try {
            $permissionData = $permission->getAttributes();

            // ✅ IMPORTANT: Before deleting, detach permission from all roles and users
            // This prevents foreign key constraint violations
            if ($permission->roles()->exists()) {
                $permission->roles()->detach();
            }
            if ($permission->users()->exists()) {
                $permission->users()->detach();
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
            \Log::error('Permission deletion failed', [
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
}
