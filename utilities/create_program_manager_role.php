<?php

/**
 * Script to create program_manager role and assign permissions
 * Run this in Tinker: include('create_program_manager_role.php');
 */

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

try {
    // Create the program_manager role
    $role = Role::create(['name' => 'program_manager']);
    echo "✓ Created program_manager role\n";

    // Define permissions for program_manager
    $permissions = [
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
        'form-templates.view',
        'form-templates.download',
    ];

    // Sync permissions to the role
    $role->syncPermissions($permissions);
    echo "✓ Assigned " . count($permissions) . " permissions to program_manager role\n";

    echo "\n✅ SUCCESS! Program Manager role created with all permissions.\n";
} catch (\Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo "⚠ Program Manager role already exists!\n";
        echo "If you want to update permissions, run:\n";
        echo "\$role = Role::findByName('program_manager');\n";
        echo "\$role->syncPermissions(['applicants.view', 'applicants.approve', ...]);\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
