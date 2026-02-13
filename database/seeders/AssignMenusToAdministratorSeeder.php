<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;

class AssignMenusToAdministratorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'administrator')->first();

        if (!$adminRole) {
            $this->command->error('Administrator role not found!');
            return;
        }

        $menuItems = MenuItem::all();

        if ($menuItems->isEmpty()) {
            $this->command->warn('No menu items found to assign.');
            return;
        }

        // Assign all menu items to administrator role
        $menuIds = $menuItems->pluck('id')->toArray();
        $adminRole->menuItems()->sync($menuIds);

        $this->command->info("Assigned {$menuItems->count()} menu items to administrator role.");
    }
}
