<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Applicants/Profiles Module
            ['name' => 'applicants.view', 'description' => 'View applicant profiles'],
            ['name' => 'applicants.create', 'description' => 'Create new applicants'],
            ['name' => 'applicants.edit', 'description' => 'Edit applicant information'],
            ['name' => 'applicants.delete', 'description' => 'Delete applicants'],
            ['name' => 'applicants.export', 'description' => 'Export applicant data'],
            ['name' => 'applicants.approve', 'description' => 'Approve/decline applications'],

            // Scholarship Records Module
            ['name' => 'scholarships.view', 'description' => 'View scholarship records'],
            ['name' => 'scholarships.create', 'description' => 'Create scholarship records'],
            ['name' => 'scholarships.edit', 'description' => 'Edit scholarship records'],
            ['name' => 'scholarships.delete', 'description' => 'Delete scholarship records'],
            ['name' => 'scholarships.export', 'description' => 'Export scholarship data'],
            ['name' => 'scholarships.update-status', 'description' => 'Update scholarship status'],
            ['name' => 'scholarships.update-grant', 'description' => 'Update grant provision'],

            // Disbursements Module
            ['name' => 'disbursements.view', 'description' => 'View disbursements'],
            ['name' => 'disbursements.create', 'description' => 'Create disbursements'],
            ['name' => 'disbursements.edit', 'description' => 'Edit disbursements'],
            ['name' => 'disbursements.delete', 'description' => 'Delete disbursements'],
            ['name' => 'disbursements.export', 'description' => 'Export disbursement data'],
            ['name' => 'disbursements.approve', 'description' => 'Approve disbursements'],

            // Waiting List Module
            ['name' => 'waiting-list.view', 'description' => 'View waiting list'],
            ['name' => 'waiting-list.manage', 'description' => 'Manage waiting list'],
            ['name' => 'waiting-list.export', 'description' => 'Export waiting list'],

            // Programs & Courses
            ['name' => 'programs.view', 'description' => 'View programs'],
            ['name' => 'programs.manage', 'description' => 'Manage programs'],
            ['name' => 'courses.view', 'description' => 'View courses'],
            ['name' => 'courses.manage', 'description' => 'Manage courses'],
            ['name' => 'schools.view', 'description' => 'View schools'],
            ['name' => 'schools.manage', 'description' => 'Manage schools'],
            ['name' => 'requirements.view', 'description' => 'View requirements'],
            ['name' => 'requirements.manage', 'description' => 'Manage requirements'],

            // Reports
            ['name' => 'reports.view', 'description' => 'View reports'],
            ['name' => 'reports.generate', 'description' => 'Generate reports'],
            ['name' => 'reports.export', 'description' => 'Export reports'],

            // System Settings
            ['name' => 'settings.view', 'description' => 'View system settings'],
            ['name' => 'settings.manage', 'description' => 'Manage system settings'],
            ['name' => 'users.view', 'description' => 'View users'],
            ['name' => 'users.manage', 'description' => 'Manage users'],
            ['name' => 'roles.view', 'description' => 'View roles'],
            ['name' => 'roles.manage', 'description' => 'Manage roles'],
            ['name' => 'permissions.view', 'description' => 'View permissions'],
            ['name' => 'permissions.manage', 'description' => 'Manage permissions'],
            ['name' => 'system-options.view', 'description' => 'View system options'],
            ['name' => 'system-options.manage', 'description' => 'Manage system options'],
            ['name' => 'system-stats.view', 'description' => 'View system statistics'],

            // JPM (Job Placement & Monitoring)
            ['name' => 'jpm.view', 'description' => 'View JPM records'],
            ['name' => 'jpm.manage', 'description' => 'Manage JPM records'],

            // Priority Management
            ['name' => 'priority.view', 'description' => 'View priority levels'],
            ['name' => 'priority.manage', 'description' => 'Manage priority levels'],

            // Documents and Forms
            ['name' => 'documents.view', 'description' => 'View documents and forms'],
            ['name' => 'documents.download', 'description' => 'Download documents and forms'],
            ['name' => 'documents.upload', 'description' => 'Upload new documents and forms'],
            ['name' => 'documents.edit', 'description' => 'Edit documents and forms'],
            ['name' => 'documents.delete', 'description' => 'Delete documents and forms'],

            // Return of Service (ROS) Module
            ['name' => 'return-of-service.view', 'description' => 'View return of service records'],
            ['name' => 'return-of-service.create', 'description' => 'Create return of service records'],
            ['name' => 'return-of-service.edit', 'description' => 'Edit return of service records'],
            ['name' => 'return-of-service.delete', 'description' => 'Delete return of service records'],
            ['name' => 'return-of-service.export', 'description' => 'Export return of service data'],

            // Admin Management
            ['name' => 'admin.manage', 'description' => 'Manage admin panel'],
            ['name' => 'admin.access', 'description' => 'Access admin panel'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['guard_name' => 'web']
            );
        }

        // Assign all permissions to administrator role
        $adminRole = Role::firstOrCreate(['name' => 'administrator']);
        $adminRole->givePermissionTo(Permission::all());

        // Assign permissions to program_manager role - like moderator but with approval capabilities
        $programManagerRole = Role::firstOrCreate(['name' => 'program_manager']);
        $programManagerPermissions = [
            'applicants.view',
            'applicants.create',
            'applicants.edit',
            'applicants.approve', // Program managers can approve/deny applications
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
            'documents.view',
            'documents.download',
            'return-of-service.view',
            'return-of-service.create',
            'return-of-service.edit',
            'return-of-service.export',
        ];
        $programManagerRole->syncPermissions($programManagerPermissions);

        // Assign limited permissions to moderator role
        $moderatorRole = Role::firstOrCreate(['name' => 'moderator']);
        $moderatorPermissions = [
            'applicants.view',
            'applicants.create',
            'applicants.edit',
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
            'documents.view',
            'documents.download',
            'return-of-service.view',
        ];
        $moderatorRole->syncPermissions($moderatorPermissions);

        // Assign view-only permissions to user role
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userPermissions = [
            'applicants.view',
            'scholarships.view',
            'disbursements.view',
            'waiting-list.view',
            'programs.view',
            'courses.view',
            'schools.view',
            'requirements.view',
            'reports.view',
            'documents.view',
            'documents.download',
            'return-of-service.view',
        ];
        $userRole->syncPermissions($userPermissions);

        // Assign JPM permissions to jpm_admin role
        $jpmRole = Role::firstOrCreate(['name' => 'jpm_admin']);
        $jpmPermissions = [
            'applicants.view',
            'scholarships.view',
            'jpm.view',
            'jpm.manage',
            'priority.view',
            'priority.manage',
            'reports.view',
            'reports.generate',
            'documents.view',
            'documents.download',
        ];
        $jpmRole->syncPermissions($jpmPermissions);

        $this->command->info('Permissions seeded successfully!');
        $this->command->info('Total permissions: ' . Permission::count());
    }
}
