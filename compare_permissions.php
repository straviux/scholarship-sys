<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PROGRAM_MANAGER PERMISSIONS ===\n";
$pmRole = \Spatie\Permission\Models\Role::where('name', 'program_manager')->first();
if ($pmRole) {
    $pmPerms = $pmRole->permissions()->pluck('name')->sort();
    echo "Total: " . $pmPerms->count() . " permissions\n";
    foreach ($pmPerms as $perm) {
        echo "  - $perm\n";
    }
} else {
    echo "program_manager role not found\n";
}

echo "\n=== ADMINISTRATOR PERMISSIONS ===\n";
$adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
if ($adminRole) {
    $adminPerms = $adminRole->permissions()->pluck('name')->sort();
    echo "Total: " . $adminPerms->count() . " permissions\n";
    foreach ($adminPerms as $perm) {
        echo "  - $perm\n";
    }
} else {
    echo "administrator role not found\n";
}

echo "\n=== PERMISSIONS IN PROGRAM_MANAGER BUT NOT IN ADMINISTRATOR ===\n";
$pmOnly = $pmPerms->diff($adminPerms);
if ($pmOnly->count() > 0) {
    foreach ($pmOnly as $perm) {
        echo "  - $perm\n";
    }
} else {
    echo "  None\n";
}

echo "\n=== PERMISSIONS IN ADMINISTRATOR BUT NOT IN PROGRAM_MANAGER ===\n";
$adminOnly = $adminPerms->diff($pmPerms);
if ($adminOnly->count() > 0) {
    foreach ($adminOnly as $perm) {
        echo "  - $perm\n";
    }
} else {
    echo "  None\n";
}
