<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CleanupPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:cleanup 
                            {--remove-from-role= : Remove all permissions from a specific role}
                            {--remove-duplicates : Remove duplicate permission records}
                            {--show-duplicates : Show duplicate permission records}
                            {--remove-from-user= : Remove all permissions from a specific user (by ID)}
                            {--clean-pivot-tables : Clean up orphaned and duplicate pivot table records}
                            {--show-pivot-issues : Show issues in pivot tables}
                            {--remove-permission= : Remove ALL instances of a specific permission from all roles and users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup and manage permissions - remove duplicates, clear role permissions, etc.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Show duplicates
        if ($this->option('show-duplicates')) {
            $this->showDuplicates();
        }

        // Show pivot issues
        if ($this->option('show-pivot-issues')) {
            $this->showPivotIssues();
        }

        // Remove duplicates
        if ($this->option('remove-duplicates')) {
            $this->removeDuplicates();
        }

        // Clean pivot tables
        if ($this->option('clean-pivot-tables')) {
            $this->cleanPivotTables();
        }

        // Remove all permissions from a role
        if ($this->option('remove-from-role')) {
            $this->removeFromRole($this->option('remove-from-role'));
        }

        // Remove all permissions from a user
        if ($this->option('remove-from-user')) {
            $this->removeFromUser($this->option('remove-from-user'));
        }

        // Remove a specific permission from all roles and users
        if ($this->option('remove-permission')) {
            $this->removePermissionCompletely($this->option('remove-permission'));
        }

        if (
            !$this->option('show-duplicates') &&
            !$this->option('show-pivot-issues') &&
            !$this->option('remove-duplicates') &&
            !$this->option('clean-pivot-tables') &&
            !$this->option('remove-from-role') &&
            !$this->option('remove-from-user') &&
            !$this->option('remove-permission')
        ) {
            $this->info('No action specified. Use --help to see available options.');
        }
    }

    /**
     * Show duplicate permissions
     */
    private function showDuplicates()
    {
        $this->info('Checking for duplicate permissions...');

        $duplicates = Permission::query()
            ->select('name', \DB::raw('COUNT(*) as count'))
            ->groupBy('name')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate permissions found.');
            return;
        }

        $this->warn('Found ' . $duplicates->count() . ' duplicate permission names:');
        foreach ($duplicates as $dup) {
            $this->line("  - {$dup->name} (appears {$dup->count} times)");

            // Show IDs of duplicates
            $ids = Permission::where('name', $dup->name)->pluck('id')->toArray();
            $this->line("    IDs: " . implode(', ', $ids));
        }
    }

    /**
     * Remove duplicate permissions (keep only one of each name)
     */
    private function removeDuplicates()
    {
        $this->info('Removing duplicate permissions...');

        $duplicates = Permission::query()
            ->select('name', \DB::raw('COUNT(*) as count'))
            ->groupBy('name')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicates to remove.');
            return;
        }

        foreach ($duplicates as $dup) {
            // Get all records with this name
            $records = Permission::where('name', $dup->name)->get();

            // Keep the first one, delete the rest
            $toDelete = $records->skip(1);

            foreach ($toDelete as $record) {
                // Sync the roles/users from this record to the one we're keeping
                $toKeep = $records->first();

                // Sync users
                foreach ($record->users as $user) {
                    if (!$toKeep->users->contains($user->id)) {
                        $user->givePermissionTo($toKeep);
                    }
                }

                // Sync roles
                foreach ($record->roles as $role) {
                    if (!$toKeep->roles->contains($role->id)) {
                        $role->givePermissionTo($toKeep);
                    }
                }

                $record->delete();
                $this->info("  Removed duplicate of: {$dup->name}");
            }
        }

        $this->info('Duplicate removal complete.');
    }

    /**
     * Remove all permissions from a role
     */
    private function removeFromRole($roleName)
    {
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            $this->error("Role '{$roleName}' not found.");
            return;
        }

        $permissionCount = $role->permissions()->count();

        if ($permissionCount === 0) {
            $this->info("Role '{$roleName}' has no permissions to remove.");
            return;
        }

        if ($this->confirm("Remove all {$permissionCount} permissions from role '{$roleName}'?")) {
            $role->syncPermissions([]);
            $this->info("✓ All permissions removed from role '{$roleName}'.");
        }
    }

    /**
     * Remove all permissions from a user
     */
    private function removeFromUser($userId)
    {
        $user = \App\Models\User::find($userId);

        if (!$user) {
            $this->error("User ID {$userId} not found.");
            return;
        }

        $permissionCount = $user->permissions()->count();

        if ($permissionCount === 0) {
            $this->info("User '{$user->name}' has no direct permissions to remove.");
            return;
        }

        if ($this->confirm("Remove all {$permissionCount} direct permissions from user '{$user->name}'?")) {
            $user->syncPermissions([]);
            $this->info("✓ All direct permissions removed from user '{$user->name}'.");
            $this->info("(User still has permissions from their assigned roles)");
        }
    }

    /**
     * Show issues in pivot tables (orphaned or duplicate records)
     */
    private function showPivotIssues()
    {
        $this->info('Checking for pivot table issues...');

        // Check for orphaned role_permission entries (permission_id doesn't exist)
        $orphanedRolePerms = \DB::table('role_has_permissions')
            ->leftJoin('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->whereNull('permissions.id')
            ->count();

        if ($orphanedRolePerms > 0) {
            $this->warn("Found {$orphanedRolePerms} orphaned entries in role_has_permissions table");
        }

        // Check for orphaned user_permission entries (permission_id doesn't exist)
        $orphanedUserPerms = \DB::table('model_has_permissions')
            ->leftJoin('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->whereNull('permissions.id')
            ->count();

        if ($orphanedUserPerms > 0) {
            $this->warn("Found {$orphanedUserPerms} orphaned entries in model_has_permissions table");
        }

        // Check for duplicate entries in pivot tables
        $dupRolePerms = \DB::table('role_has_permissions')
            ->select('role_id', 'permission_id', \DB::raw('COUNT(*) as count'))
            ->groupBy('role_id', 'permission_id')
            ->having('count', '>', 1)
            ->count();

        if ($dupRolePerms > 0) {
            $this->warn("Found {$dupRolePerms} duplicate entries in role_has_permissions table");
        }

        $dupUserPerms = \DB::table('model_has_permissions')
            ->select('model_id', 'permission_id', \DB::raw('COUNT(*) as count'))
            ->groupBy('model_id', 'permission_id')
            ->having('count', '>', 1)
            ->count();

        if ($dupUserPerms > 0) {
            $this->warn("Found {$dupUserPerms} duplicate entries in model_has_permissions table");
        }

        if ($orphanedRolePerms == 0 && $orphanedUserPerms == 0 && $dupRolePerms == 0 && $dupUserPerms == 0) {
            $this->info('No pivot table issues found.');
        }
    }

    /**
     * Clean up pivot tables - remove orphaned and duplicate records
     */
    private function cleanPivotTables()
    {
        $this->info('Cleaning pivot tables...');

        // Remove orphaned entries in role_has_permissions
        $deletedOrphanedRole = \DB::table('role_has_permissions')
            ->leftJoin('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->whereNull('permissions.id')
            ->delete();

        if ($deletedOrphanedRole > 0) {
            $this->info("✓ Removed {$deletedOrphanedRole} orphaned entries from role_has_permissions");
        }

        // Remove orphaned entries in model_has_permissions
        $deletedOrphanedUser = \DB::table('model_has_permissions')
            ->leftJoin('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->whereNull('permissions.id')
            ->delete();

        if ($deletedOrphanedUser > 0) {
            $this->info("✓ Removed {$deletedOrphanedUser} orphaned entries from model_has_permissions");
        }

        // Remove duplicate entries in role_has_permissions (keep only first)
        $dupRolePerms = \DB::table('role_has_permissions')
            ->select('role_id', 'permission_id', \DB::raw('MIN(id) as min_id'))
            ->groupBy('role_id', 'permission_id')
            ->having(\DB::raw('COUNT(*)'), '>', 1)
            ->pluck('min_id');

        if ($dupRolePerms->count() > 0) {
            $deletedDupRole = \DB::table('role_has_permissions')
                ->where(function ($query) use ($dupRolePerms) {
                    $query->selectRaw('COUNT(*)')
                        ->from('role_has_permissions as rp2')
                        ->whereRaw('rp2.role_id = role_has_permissions.role_id AND rp2.permission_id = role_has_permissions.permission_id')
                        ->havingRaw('COUNT(*) > 1');
                })
                ->whereNotIn('id', $dupRolePerms)
                ->delete();

            if ($deletedDupRole > 0) {
                $this->info("✓ Removed {$deletedDupRole} duplicate entries from role_has_permissions");
            }
        }

        // Remove duplicate entries in model_has_permissions (keep only first)
        $dupUserPerms = \DB::table('model_has_permissions')
            ->select('model_id', 'permission_id', \DB::raw('MIN(id) as min_id'))
            ->groupBy('model_id', 'permission_id')
            ->having(\DB::raw('COUNT(*)'), '>', 1)
            ->pluck('min_id');

        if ($dupUserPerms->count() > 0) {
            $deletedDupUser = \DB::table('model_has_permissions')
                ->where(function ($query) use ($dupUserPerms) {
                    $query->selectRaw('COUNT(*)')
                        ->from('model_has_permissions as mhp2')
                        ->whereRaw('mhp2.model_id = model_has_permissions.model_id AND mhp2.permission_id = model_has_permissions.permission_id')
                        ->havingRaw('COUNT(*) > 1');
                })
                ->whereNotIn('id', $dupUserPerms)
                ->delete();

            if ($deletedDupUser > 0) {
                $this->info("✓ Removed {$deletedDupUser} duplicate entries from model_has_permissions");
            }
        }

        $this->info('Pivot table cleanup complete.');
    }

    /**
     * Remove a specific permission from all roles and users completely
     */
    private function removePermissionCompletely($permissionName)
    {
        $permission = Permission::where('name', $permissionName)->first();

        if (!$permission) {
            $this->error("Permission '{$permissionName}' not found.");
            return;
        }

        // Count how many roles and users have this permission
        $roleCount = $permission->roles()->count();
        $userCount = $permission->users()->count();

        $this->warn("Permission '{$permissionName}' is assigned to:");
        $this->info("  - {$roleCount} roles");
        $this->info("  - {$userCount} users");

        if ($this->confirm("Remove this permission from all roles and users?")) {
            $permission->roles()->detach();
            $permission->users()->detach();

            $this->info("✓ Removed '{$permissionName}' from all roles and users.");
        }
    }
}
