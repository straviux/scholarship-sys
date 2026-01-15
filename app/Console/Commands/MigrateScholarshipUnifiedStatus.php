<?php

namespace App\Console\Commands;

use App\Models\ScholarshipRecord;
use Illuminate\Console\Command;

class MigrateScholarshipUnifiedStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scholarship:migrate-unified-status {--fix : Automatically fix records with null unified_status} {--verify : Only verify records without exiting}';

    /**
     * The console description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy approval_status + scholarship_status to unified_status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fix = $this->option('fix');
        $verify = $this->option('verify');

        $this->info('🔍 Checking scholarship records for unified_status migration...');

        // Find records with null unified_status
        $nullRecords = ScholarshipRecord::whereNull('unified_status')->get();

        if ($nullRecords->isEmpty()) {
            $this->info('✅ All records have unified_status set! Migration complete.');
            return 0;
        }

        $count = $nullRecords->count();
        $this->warn("⚠️  Found {$count} record(s) with null unified_status");

        // Display sample records
        $this->info("\n📋 Sample records needing migration:");
        $nullRecords->take(5)->each(function ($record, $index) {
            $this->line(sprintf(
                "  %d. ID: %s | Approval: %s | Scholarship: %s",
                $index + 1,
                $record->id,
                $record->approval_status ?? 'null',
                $record->scholarship_status ?? 'null'
            ));
        });

        if ($count > 5) {
            $this->line("  ... and " . ($count - 5) . " more");
        }

        if ($verify) {
            $this->info("\n✅ Verification complete. No changes made (--verify mode)");
            return 0;
        }

        if (!$fix) {
            if (!$this->confirm("\n🔧 Fix all {$count} record(s) with auto-migration?")) {
                $this->info("❌ Migration cancelled.");
                return 0;
            }
        }

        // Migrate records
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $updated = 0;
        $errors = [];

        $nullRecords->each(function ($record) use (&$bar, &$updated, &$errors) {
            try {
                $unifiedStatus = $this->mapLegacyStatus(
                    $record->approval_status,
                    $record->scholarship_status
                );

                $record->update(['unified_status' => $unifiedStatus]);
                $updated++;
            } catch (\Exception $e) {
                $errors[] = [
                    'id' => $record->id,
                    'error' => $e->getMessage()
                ];
            }
            $bar->advance();
        });

        $bar->finish();

        $this->info("\n\n✅ Migration complete!");
        $this->line("📊 Updated: {$updated} record(s)");

        if (!empty($errors)) {
            $this->error("❌ Errors: " . count($errors) . " record(s)");
            foreach ($errors as $error) {
                $this->line("  - ID {$error['id']}: {$error['error']}");
            }
            return 1;
        }

        return 0;
    }

    /**
     * Map legacy approval_status + scholarship_status to unified_status
     */
    private function mapLegacyStatus($approvalStatus, $scholarshipStatus)
    {
        // Declined takes priority
        if ($approvalStatus === 'declined') {
            return 'declined';
        }

        // Pending approval
        if (in_array($approvalStatus, ['pending', 'conditionally-approved'])) {
            return 'pending_approval';
        }

        // Approved - check scholarship_status
        if ($approvalStatus === 'approved') {
            if ($scholarshipStatus === 3) {
                return 'completed';
            }
            return 'active_scholar';
        }

        return 'unknown';
    }
}
