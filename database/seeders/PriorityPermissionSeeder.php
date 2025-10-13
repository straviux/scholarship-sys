<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PriorityPermissionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create the permission if it doesn't exist
        $permission = Permission::firstOrCreate([
            'name' => 'can-manage-priority',
            'guard_name' => 'web'
        ]);

        // Assign to administrator role by default
        $adminRole = Role::where('name', 'administrator')->first();
        if ($adminRole && !$adminRole->hasPermissionTo($permission)) {
            $adminRole->givePermissionTo($permission);
        }

        $this->command->info('Priority permission created and assigned to administrator role.');
    }
}
