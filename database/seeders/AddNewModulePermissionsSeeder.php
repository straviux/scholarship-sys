<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddNewModulePermissionsSeeder extends Seeder
{
    /**
     * Example: Adding permissions for a new module
     * 
     * Usage:
     * 1. Copy this seeder and rename it for your module
     * 2. Update the $newPermissions array
     * 3. Run: php artisan db:seed --class=YourNewSeederName
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Example: Adding permissions for a new "Announcements" module
        $newPermissions = [
            ['name' => 'announcements.view', 'description' => 'View announcements'],
            ['name' => 'announcements.create', 'description' => 'Create announcements'],
            ['name' => 'announcements.edit', 'description' => 'Edit announcements'],
            ['name' => 'announcements.delete', 'description' => 'Delete announcements'],
            ['name' => 'announcements.publish', 'description' => 'Publish announcements'],
        ];

        foreach ($newPermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['guard_name' => 'web']
            );
        }

        // Automatically give all new permissions to administrator
        $adminRole = Role::findByName('administrator');
        $adminRole->givePermissionTo(Permission::all());

        // Optionally assign to other roles
        $moderatorRole = Role::findByName('moderator');
        $moderatorRole->givePermissionTo([
            'announcements.view',
            'announcements.create',
            'announcements.edit',
        ]);

        $this->command->info('New module permissions added successfully!');
        $this->command->info('Total permissions now: ' . Permission::count());
    }
}
