<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing menu items to avoid duplicates
        $this->command->info('Clearing existing menu items...');
        MenuItem::query()->delete();

        // All menu items as top-level (no parent groups initially)
        // Users can create their own group structure via the UI

        MenuItem::create([
            'name' => 'Home',
            'icon' => 'pi pi-home',
            'route' => 'home.index',
            'order' => 1,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Dashboard',
            'icon' => 'pi pi-chart-bar',
            'route' => 'dashboard',
            'permission' => 'dashboard.view',
            'order' => 2,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Forms & Letters',
            'icon' => 'pi pi-file',
            'route' => 'form-templates.index',
            'permission' => 'form-templates.view',
            'order' => 3,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Waiting List',
            'icon' => 'pi pi-clipboard',
            'route' => 'waitinglist.index',
            'permission' => 'applicants.view',
            'order' => 4,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Reviewed Applicants',
            'icon' => 'pi pi-check-circle',
            'route' => 'scholarship.reviewed-applicants',
            'permission' => 'applicants.approve',
            'order' => 5,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Profiles',
            'icon' => 'pi pi-users',
            'route' => 'scholarship.profiles',
            'permission' => 'scholarships.view',
            'order' => 6,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Vouchers',
            'icon' => 'pi pi-credit-card',
            'route' => 'vouchers.index',
            'permission' => 'vouchers.view',
            'order' => 7,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'System Updates',
            'icon' => 'pi pi-bell',
            'route' => 'system-updates.index',
            'order' => 8,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Help & Instructions',
            'icon' => 'pi pi-question-circle',
            'route' => 'help.index',
            'order' => 9,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Programs',
            'icon' => 'pi pi-book',
            'route' => 'scholarshipprograms.index',
            'permission' => 'programs.view',
            'order' => 10,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Courses',
            'icon' => 'pi pi-graduation-cap',
            'route' => 'courses.index',
            'permission' => 'courses.view',
            'order' => 11,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Requirements',
            'icon' => 'pi pi-list',
            'route' => 'program_requirements.index',
            'permission' => 'requirements.view',
            'order' => 12,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Schools',
            'icon' => 'pi pi-building',
            'route' => 'school.index',
            'permission' => 'schools.view',
            'order' => 13,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Responsibility Centers',
            'icon' => 'pi pi-code',
            'route' => 'responsibility-centers.index',
            'permission' => 'responsibility-centers.view',
            'order' => 14,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Option Values',
            'icon' => 'pi pi-sliders-h',
            'route' => 'system-options.index',
            'order' => 15,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Access Control',
            'icon' => 'pi pi-lock',
            'route' => 'access-control.index',
            'order' => 16,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'System Stats',
            'icon' => 'pi pi-chart-bar',
            'route' => 'admin.system-report',
            'order' => 17,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Deleted Records',
            'icon' => 'pi pi-trash',
            'route' => 'admin.deleted-records',
            'order' => 18,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Data Export',
            'icon' => 'pi pi-download',
            'route' => 'data-export.index',
            'order' => 19,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Manage Updates',
            'icon' => 'pi pi-megaphone',
            'route' => 'admin.system-updates',
            'order' => 20,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Maintenance',
            'icon' => 'pi pi-cog',
            'route' => 'admin.maintenance.index',
            'order' => 21,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Menu Item Management',
            'icon' => 'pi pi-list',
            'route' => 'admin.menu-items.index',
            'order' => 22,
            'is_active' => true,
            'is_group' => false,
        ]);

        MenuItem::create([
            'name' => 'Role Menu Management',
            'icon' => 'pi pi-align-justify',
            'route' => 'admin.role-menus.index',
            'order' => 23,
            'is_active' => true,
            'is_group' => false,
        ]);

        $this->command->info('Menu items seeded successfully with is_group flag!');
    }
}
