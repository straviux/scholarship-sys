<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class TestJasperStarter extends Command
{
    protected $signature = 'jasper:test';
    protected $description = 'Test JasperStarter CLI installation and configuration';

    public function handle()
    {
        $this->info('🔍 Testing JasperStarter Configuration...\n');

        // Test 1: Check if JasperStarter is in PATH
        $this->info('1️⃣  Checking if JasperStarter is in PATH...');
        try {
            $result = Process::run('jasperstarter -v');
            if ($result->exitCode() === 0) {
                $this->info('   ✅ JasperStarter found in PATH');
                $this->info('   Output: ' . trim($result->output()));
            } else {
                $this->warn('   ⚠️  JasperStarter found but returned error');
                $this->warn('   Error: ' . trim($result->errorOutput()));
            }
        } catch (\Exception $e) {
            $this->error('   ❌ JasperStarter not found in PATH');
            $this->error('   Error: ' . $e->getMessage());
        }

        $this->line('');

        // Test 2: Check Java installation
        $this->info('2️⃣  Checking if Java is installed...');
        try {
            $result = Process::run('java -version');
            if ($result->exitCode() === 0) {
                $this->info('   ✅ Java is installed');
                // Java version output goes to stderr on some systems
                $output = $result->output() ?: $result->errorOutput();
                foreach (explode("\n", trim($output)) as $line) {
                    $this->info('   ' . $line);
                }
            } else {
                $this->error('   ❌ Java not found');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Java not found');
            $this->error('   Error: ' . $e->getMessage());
        }

        $this->line('');

        // Test 3: Check configuration
        $this->info('3️⃣  Checking JasperReports configuration...');
        $javaHome = env('JAVA_HOME', 'Not set');
        $jasterStarterBin = env('JASPERSTARTER_BIN', 'Not set');
        $jasperEnabled = env('JASPER_ENABLED', 'false');

        $this->info("   JASPER_ENABLED: {$jasperEnabled}");
        $this->info("   JAVA_HOME: {$javaHome}");
        $this->info("   JASPERSTARTER_BIN: {$jasterStarterBin}");

        $this->line('');

        // Test 4: Check directories
        $this->info('4️⃣  Checking required directories...');
        $dirs = [
            config('jasperreports.templates.base_path'),
            config('jasperreports.templates.compiled_path'),
            config('jasperreports.output.path'),
        ];

        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $this->info("   ✅ {$dir} exists");
            } else {
                $this->warn("   ⚠️  {$dir} does not exist");
                if (@mkdir($dir, 0755, true)) {
                    $this->info("      Created {$dir}");
                }
            }
        }

        $this->line('');

        // Test 5: Try to execute with explicit Java path
        $this->info('5️⃣  Testing JasperStarter with explicit Java path...');
        try {
            // Find Java executable
            $javaPath = $this->findJava();
            if ($javaPath) {
                $this->info("   Found Java at: {$javaPath}");

                // Try running jasperstarter with Java path in environment
                $command = 'set JAVA_HOME=' . dirname(dirname($javaPath)) . ' && jasperstarter -v';
                $result = Process::run($command);

                if ($result->exitCode() === 0) {
                    $this->info('   ✅ JasperStarter works with Java!');
                    $this->info('   ' . trim($result->output()));
                } else {
                    $this->warn('   ⚠️  JasperStarter returned error');
                    $this->warn('   ' . trim($result->errorOutput()));
                }
            } else {
                $this->error('   ❌ Could not find Java executable');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Error testing JasperStarter');
            $this->error('   ' . $e->getMessage());
        }

        $this->line('');
        $this->info('✨ Test complete!');

        // Recommendations
        $this->line('');
        $this->info('📋 Recommendations:');
        if (env('JASPER_ENABLED') !== 'true') {
            $this->warn('  • Set JASPER_ENABLED=true in .env');
        }
        if (env('JAVA_HOME') === null) {
            $this->warn('  • Set JAVA_HOME environment variable in .env');
        }
        if (env('JASPERSTARTER_BIN') === null) {
            $this->warn('  • Set JASPERSTARTER_BIN environment variable in .env');
        }
    }

    protected function findJava()
    {
        try {
            $result = Process::run('where java');
            if ($result->exitCode() === 0) {
                return trim($result->output());
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Common paths
        $commonPaths = [
            'C:\\Program Files\\Eclipse Adoptium\\jdk-17.0.10.7-hotspot\\bin\\java.exe',
            'C:\\Program Files\\Java\\jdk-17\\bin\\java.exe',
            'C:\\Program Files (x86)\\Java\\jdk-17\\bin\\java.exe',
        ];

        foreach ($commonPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
