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
                            {--remove-from-user= : Remove all permissions from a specific user (by ID)}';

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

        // Remove duplicates
        if ($this->option('remove-duplicates')) {
            $this->removeDuplicates();
        }

        // Remove all permissions from a role
        if ($this->option('remove-from-role')) {
            $this->removeFromRole($this->option('remove-from-role'));
        }

        // Remove all permissions from a user
        if ($this->option('remove-from-user')) {
            $this->removeFromUser($this->option('remove-from-user'));
        }

        if (
            !$this->option('show-duplicates') &&
            !$this->option('remove-duplicates') &&
            !$this->option('remove-from-role') &&
            !$this->option('remove-from-user')
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
}
