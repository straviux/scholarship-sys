<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get all users with program_manager role
$users = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'program_manager'))->get();

echo "Users with program_manager role:\n";
if ($users->isEmpty()) {
    echo "  No users found with program_manager role!\n";
} else {
    foreach ($users as $user) {
        echo "  - " . $user->name . " (ID: " . $user->id . ")\n";
        echo "    Roles: " . $user->roles()->pluck('name')->implode(', ') . "\n";
    }
}

echo "\nAll users in system:\n";
$allUsers = \App\Models\User::all();
foreach ($allUsers as $user) {
    echo "  - " . $user->name . " -> " . ($user->roles()->pluck('name')->isEmpty() ? 'NO ROLES' : $user->roles()->pluck('name')->implode(', ')) . "\n";
}
