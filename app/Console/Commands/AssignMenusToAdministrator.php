<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignMenusToAdministrator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:assign-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign all menu items to administrator role (run after seeding menus)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Assigning all menu items to administrator role...');

        // Get administrator role ID
        $adminRole = DB::table('roles')->where('name', 'administrator')->first();

        if (!$adminRole) {
            $this->error('Administrator role not found!');
            return 1;
        }

        // Get all menu items
        $menuItems = DB::table('menu_items')->get();

        if ($menuItems->isEmpty()) {
            $this->warn('No menu items found.');
            return 0;
        }

        // Delete existing assignments for administrator
        DB::table('role_menu_item')->where('role_id', $adminRole->id)->delete();

        // Insert new assignments
        $assignments = [];
        $order = 0;
        foreach ($menuItems as $menuItem) {
            $assignments[] = [
                'role_id' => $adminRole->id,
                'menu_item_id' => $menuItem->id,
                'order' => $order++,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('role_menu_item')->insert($assignments);

        $this->info("Successfully assigned {$menuItems->count()} menu items to administrator role.");

        // Clear cache
        \Illuminate\Support\Facades\Cache::flush();
        $this->info("Cache cleared.");

        return 0;
    }
}
