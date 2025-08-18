<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'manage-scholarship-programs']);
        Permission::create(['name' => 'manage-program-courses']);
        Permission::create(['name' => 'create-scholar-profile']);
        // Role::create(['name' => 'admin']);
    }
}
