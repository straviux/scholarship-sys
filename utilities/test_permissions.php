<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find program_manager user
$user = \App\Models\User::where('name', 'Arcee')->first();

if (!$user) {
    echo "User not found\n";
    exit;
}

echo "User: " . $user->name . "\n";
echo "Roles: " . $user->roles()->pluck('name')->implode(', ') . "\n";
echo "Has program_manager role? " . ($user->hasRole('program_manager') ? 'YES' : 'NO') . "\n";
echo "Has administrator role? " . ($user->hasRole('administrator') ? 'YES' : 'NO') . "\n";

// Check specific permissions
echo "\nPermissions check:\n";
echo "- applicants.view: " . ($user->can('applicants.view') ? 'YES' : 'NO') . "\n";
echo "- applicants.create: " . ($user->can('applicants.create') ? 'YES' : 'NO') . "\n";
echo "- applicants.edit: " . ($user->can('applicants.edit') ? 'YES' : 'NO') . "\n";
echo "- applicants.approve: " . ($user->can('applicants.approve') ? 'YES' : 'NO') . "\n";
echo "- create-scholar-profile: " . ($user->can('create-scholar-profile') ? 'YES' : 'NO') . "\n";

// Check role based authorization
echo "\nRole check:\n";
echo "- hasAnyRole(['administrator', 'program_manager']): " . ($user->hasAnyRole(['administrator', 'program_manager']) ? 'YES' : 'NO') . "\n";
