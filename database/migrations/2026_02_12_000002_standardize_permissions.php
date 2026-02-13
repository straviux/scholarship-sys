<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create missing permissions
        $permissions = [
            ['name' => 'admin.manage', 'description' => 'Manage admin panel'],
            ['name' => 'admin.access', 'description' => 'Access admin panel (deprecated, use admin.manage)'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['description' => $permission['description'] ?? null]
            );
        }

        // Migrate old permission names to new ones
        $permissionMigrations = [
            'can-manage-priority' => 'priority.manage',
            'create-scholar-profile' => 'applicants.create',
            'edit-scholar-profile' => 'applicants.edit',
        ];

        foreach ($permissionMigrations as $oldName => $newName) {
            $oldPermission = Permission::where('name', $oldName)->first();
            if ($oldPermission) {
                // Create new permission if it doesn't exist
                $newPermission = Permission::firstOrCreate(
                    ['name' => $newName, 'guard_name' => 'web']
                );

                // Migrate all role permissions
                $roles = Role::all();
                foreach ($roles as $role) {
                    if ($role->hasPermissionTo($oldPermission)) {
                        $role->givePermissionTo($newPermission);
                        $role->revokePermissionTo($oldPermission);
                    }
                }

                // Delete old permission
                $oldPermission->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert migrations
        $permissionMigrations = [
            'can-manage-priority' => 'priority.manage',
            'create-scholar-profile' => 'applicants.create',
            'edit-scholar-profile' => 'applicants.edit',
        ];

        foreach ($permissionMigrations as $oldName => $newName) {
            $newPermission = Permission::where('name', $newName)->first();
            if ($newPermission) {
                // Create old permission if it doesn't exist
                $oldPermission = Permission::firstOrCreate(
                    ['name' => $oldName, 'guard_name' => 'web']
                );

                // Migrate all role permissions back
                $roles = Role::all();
                foreach ($roles as $role) {
                    if ($role->hasPermissionTo($newPermission)) {
                        $role->givePermissionTo($oldPermission);
                        $role->revokePermissionTo($newPermission);
                    }
                }
            }
        }
    }
};
