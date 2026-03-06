<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Prevent seeding in production environment
        if (app()->environment('production')) {
            $this->command->warn('⚠️ Seeding is disabled in production environment');
            return;
        }

        // User::factory(10)->create();

        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(MenuItemSeeder::class);
        $this->call(RequirementsSeeder::class);
        $this->call(PopulateProfileRequirementsSeeder::class);
    }
}
