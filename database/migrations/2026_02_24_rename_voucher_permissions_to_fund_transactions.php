<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing vouchers.view permission to fund_transactions.view
        $permission = Permission::where('name', 'vouchers.view')->first();
        if ($permission) {
            $permission->update(['name' => 'fund_transactions.view']);
        } else {
            // If vouchers.view doesn't exist, create fund_transactions.view
            Permission::create(['name' => 'fund_transactions.view']);
        }

        // Create other fund_transactions permissions if they don't exist
        $permissions = ['fund_transactions.create', 'fund_transactions.edit', 'fund_transactions.delete'];
        foreach ($permissions as $perm) {
            if (!Permission::where('name', $perm)->exists()) {
                Permission::create(['name' => $perm]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally rename back or delete
        $permission = Permission::where('name', 'fund_transactions.view')->first();
        if ($permission) {
            $permission->update(['name' => 'vouchers.view']);
        }
    }
};
