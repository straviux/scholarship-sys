<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pmRole = \Spatie\Permission\Models\Role::where('name', 'program_manager')->first();

if (!$pmRole) {
    echo "program_manager role not found\n";
    exit;
}

// Add missing permissions
$newPermissions = [
    'create-scholar-profile',
    'applicants.delete',
    'applicants.export',
    'waiting-list.export'
];

foreach ($newPermissions as $permName) {
    $perm = \Spatie\Permission\Models\Permission::where('name', $permName)->first();
    if ($perm) {
        if (!$pmRole->hasPermissionTo($permName)) {
            $pmRole->givePermissionTo($perm);
            echo "✓ Added permission: $permName\n";
        } else {
            echo "- Already has: $permName\n";
        }
    } else {
        echo "✗ Permission not found: $permName\n";
    }
}

echo "\nProgram manager now has " . $pmRole->permissions()->count() . " permissions\n";
