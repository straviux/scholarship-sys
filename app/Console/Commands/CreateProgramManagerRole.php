<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateProgramManagerRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'role:create-program-manager';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the program_manager role with appropriate permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating Program Manager Role...');

        try {
            // Check if role already exists
            $role = Role::where('name', 'program_manager')->first();

            if ($role) {
                $this->line('ℹ  Program Manager role already exists.');
                $this->line('Updating permissions...');
            } else {
                $role = Role::create(['name' => 'program_manager']);
                $this->line('✓ Created program_manager role');
            }

            // Define permissions for program_manager
            $permissionsList = [
                'applicants.view',
                'applicants.create',
                'applicants.edit',
                'applicants.approve',
                'scholarships.view',
                'scholarships.create',
                'scholarships.edit',
                'scholarships.update-status',
                'disbursements.view',
                'disbursements.create',
                'disbursements.edit',
                'waiting-list.view',
                'waiting-list.manage',
                'programs.view',
                'courses.view',
                'schools.view',
                'requirements.view',
                'reports.view',
                'reports.generate',
                'form-templates.view',
                'form-templates.download',
            ];

            // Get permission objects
            $permissions = Permission::whereIn('name', $permissionsList)->get();

            if ($permissions->isEmpty()) {
                $this->error('No permissions found. Make sure PermissionSeeder has run first!');
                return 1;
            }

            // Sync permissions to the role
            $role->syncPermissions($permissions);
            $this->line('✓ Assigned ' . count($permissions) . ' permissions to program_manager role');

            $this->line('');
            $this->line('Permissions assigned:');
            foreach ($permissionsList as $perm) {
                $this->line("  • $perm");
            }

            $this->info('✓ SUCCESS! Program Manager role is ready!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
