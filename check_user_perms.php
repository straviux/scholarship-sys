<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::find(3);
if ($user) {
    echo "User ID 3: " . $user->name . "\n";
    echo "Roles: " . $user->roles->pluck('name')->join(', ') . "\n";
    echo "Has can-manage-priority: " . ($user->hasPermissionTo('can-manage-priority') ? 'YES' : 'NO') . "\n";
} else {
    echo "User 3 not found\n";
}
