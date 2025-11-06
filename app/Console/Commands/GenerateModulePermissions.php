<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GenerateModulePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:permissions 
                            {module : The module name (e.g., announcements, documents)}
                            {--actions=* : Custom actions (default: view,create,edit,delete)}
                            {--assign : Automatically assign permissions to roles}
                            {--seeder : Generate a seeder file}
                            {--run : Run the seeder immediately (implies --seeder)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate permissions for a module with optional seeder creation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $module = $this->argument('module');
        $moduleName = Str::slug($module);
        $moduleTitle = Str::title(str_replace(['-', '_'], ' ', $moduleName));

        // Get actions - use provided or defaults
        $actions = $this->option('actions');
        if (empty($actions)) {
            $actions = ['view', 'create', 'edit', 'delete'];
        }

        $this->info("🚀 Generating permissions for: {$moduleTitle}");
        $this->newLine();

        // Generate permissions
        $permissions = $this->generatePermissions($moduleName, $actions);

        // Create permissions in database
        $this->createPermissions($permissions);

        // Assign to roles if requested
        if ($this->option('assign')) {
            $this->assignPermissionsToRoles($permissions);
        }

        // Generate seeder if requested
        if ($this->option('seeder') || $this->option('run')) {
            $seederPath = $this->generateSeeder($moduleName, $moduleTitle, $permissions);

            if ($this->option('run')) {
                $this->runSeeder($moduleName);
            } else {
                $this->info("✅ Seeder created: {$seederPath}");
                $this->comment("   Run: php artisan db:seed --class=" . Str::studly($moduleName) . "PermissionsSeeder");
            }
        }

        $this->newLine();
        $this->info('✨ Done! Next steps:');
        $this->comment('   1. Add routes with permission middleware');
        $this->comment('   2. Use permissions in your Vue components');
        $this->comment('   3. Assign permissions to roles via Admin UI');
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Generate permission array
     */
    protected function generatePermissions(string $module, array $actions): array
    {
        $permissions = [];

        foreach ($actions as $action) {
            $permissions[] = [
                'name' => "{$module}.{$action}",
                'description' => ucfirst($action) . ' ' . str_replace(['-', '_'], ' ', $module),
            ];
        }

        return $permissions;
    }

    /**
     * Create permissions in database
     */
    protected function createPermissions(array $permissions): void
    {
        $this->info('📝 Creating permissions in database...');

        foreach ($permissions as $permission) {
            $perm = Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['guard_name' => 'web']
            );

            if ($perm->wasRecentlyCreated) {
                $this->line("   ✓ Created: {$permission['name']}");
            } else {
                $this->comment("   ⚠ Already exists: {$permission['name']}");
            }
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->newLine();
    }

    /**
     * Assign permissions to roles
     */
    protected function assignPermissionsToRoles(array $permissions): void
    {
        $this->info('🔐 Assigning permissions to roles...');

        $permissionNames = collect($permissions)->pluck('name')->toArray();

        // Administrator gets all permissions
        $adminRole = Role::findByName('administrator');
        $adminRole->givePermissionTo($permissionNames);
        $this->line("   ✓ Assigned all to: administrator");

        // Ask for other roles
        if ($this->confirm('Assign permissions to moderator role?', true)) {
            $moderatorRole = Role::findByName('moderator');

            // Default: give view, create, edit (not delete)
            $moderatorPerms = collect($permissionNames)
                ->filter(fn($p) => !str_ends_with($p, '.delete'))
                ->toArray();

            $moderatorRole->givePermissionTo($moderatorPerms);
            $this->line("   ✓ Assigned " . count($moderatorPerms) . " permissions to: moderator");
        }

        if ($this->confirm('Assign view permission to user role?', true)) {
            $userRole = Role::findByName('user');
            $viewPermission = collect($permissionNames)
                ->filter(fn($p) => str_ends_with($p, '.view'))
                ->first();

            if ($viewPermission) {
                $userRole->givePermissionTo($viewPermission);
                $this->line("   ✓ Assigned view permission to: user");
            }
        }

        $this->newLine();
    }

    /**
     * Generate seeder file
     */
    protected function generateSeeder(string $module, string $moduleTitle, array $permissions): string
    {
        $className = Str::studly($module) . 'PermissionsSeeder';
        $seederPath = database_path("seeders/{$className}.php");

        $permissionsArray = $this->formatPermissionsForSeeder($permissions);

        $seederContent = <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class {$className} extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define {$moduleTitle} permissions
        \$permissions = {$permissionsArray};

        // Create permissions
        foreach (\$permissions as \$permission) {
            Permission::firstOrCreate(
                ['name' => \$permission['name']],
                ['guard_name' => 'web']
            );
        }

        // Assign all permissions to administrator
        \$adminRole = Role::findByName('administrator');
        \$adminRole->givePermissionTo(Permission::whereIn('name', array_column(\$permissions, 'name'))->get());

        // Optionally assign to other roles
        // \$moderatorRole = Role::findByName('moderator');
        // \$moderatorRole->givePermissionTo(['{$module}.view', '{$module}.create', '{$module}.edit']);

        // \$userRole = Role::findByName('user');
        // \$userRole->givePermissionTo(['{$module}.view']);

        \$this->command->info('{$moduleTitle} permissions created successfully!');
    }
}
PHP;

        file_put_contents($seederPath, $seederContent);

        $this->info("📄 Seeder generated: database/seeders/{$className}.php");

        return $seederPath;
    }

    /**
     * Format permissions array for seeder file
     */
    protected function formatPermissionsForSeeder(array $permissions): string
    {
        $formatted = "[\n";
        foreach ($permissions as $permission) {
            $formatted .= "            ['name' => '{$permission['name']}', 'description' => '{$permission['description']}'],\n";
        }
        $formatted .= "        ]";

        return $formatted;
    }

    /**
     * Run the generated seeder
     */
    protected function runSeeder(string $module): void
    {
        $className = Str::studly($module) . 'PermissionsSeeder';

        $this->info("🎯 Running seeder...");
        $this->call('db:seed', ['--class' => $className]);
    }
}
