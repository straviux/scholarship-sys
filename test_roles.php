<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check roles
$roles = \Spatie\Permission\Models\Role::pluck('name');
echo "Roles in database:\n";
foreach ($roles as $role) {
    echo "  - $role\n";
}

// Check if program_manager role exists
$pmRole = \Spatie\Permission\Models\Role::where('name', 'program_manager')->first();
if ($pmRole) {
    echo "\n✓ program_manager role exists\n";
    echo "Permissions: " . $pmRole->permissions()->pluck('name')->count() . "\n";
} else {
    echo "\n✗ program_manager role does NOT exist\n";
}
