<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$permission = \Spatie\Permission\Models\Permission::firstOrCreate([
    'name' => 'can-manage-priority',
    'guard_name' => 'web'
]);

$jpmAdminRole = \Spatie\Permission\Models\Role::where('name', 'jpm_admin')->first();
if ($jpmAdminRole) {
    $jpmAdminRole->givePermissionTo($permission);
    echo "Permission 'can-manage-priority' assigned to 'jpm_admin' role\n";
} else {
    echo "jpm_admin role not found\n";
}

// Verify
$user = \App\Models\User::find(3);
echo "User now has can-manage-priority: " . ($user->hasPermissionTo('can-manage-priority') ? 'YES' : 'NO') . "\n";
