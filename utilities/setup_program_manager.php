#!/usr/bin/env php
<?php

require __DIR__ . '/bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "\n🔄 Creating Program Manager Role...\n\n";

try {
    // Check if role already exists
    $existingRole = Role::where('name', 'program_manager')->first();

    if ($existingRole) {
        echo "ℹ Program Manager role already exists.\n";
        echo "🔄 Updating permissions...\n\n";
        $role = $existingRole;
    } else {
        $role = Role::create(['name' => 'program_manager']);
        echo "✅ Created program_manager role\n\n";
    }

    // Define permissions for program_manager
    $permissionsList = [
        'applicants.view',
        'applicants.create',
        'applicants.edit',
        'applicants.approve',
        'scholarships.view',
        'scholarships.create',
        'scholarships.edit',
        'scholarships.update-status',
        'disbursements.view',
        'disbursements.create',
        'disbursements.edit',
        'waiting-list.view',
        'waiting-list.manage',
        'programs.view',
        'courses.view',
        'schools.view',
        'requirements.view',
        'reports.view',
        'reports.generate',
        'forms-templates.view',
        'forms-templates.download',
    ];

    // Get permission objects
    $permissions = Permission::whereIn('name', $permissionsList)->get();

    if ($permissions->isEmpty()) {
        echo "❌ Error: No permissions found. Make sure PermissionSeeder has run.\n";
        exit(1);
    }

    // Sync permissions to the role
    $role->syncPermissions($permissions);
    echo "✅ Assigned " . count($permissions) . " permissions to program_manager role\n";
    echo "\n   Permissions assigned:\n";
    foreach ($permissionsList as $perm) {
        echo "   • $perm\n";
    }

    echo "\n✅ SUCCESS! Program Manager role is ready!\n\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
