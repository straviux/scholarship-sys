<?php

namespace App\Console\Commands;

use App\Models\ScholarshipRecord;
use Illuminate\Console\Command;

class MigrateToUnifiedStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:unified-status {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate scholarship records to use unified_status exclusively. Removes dependency on approval_status and scholarship_status.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in DRY-RUN mode. No changes will be made.');
        }

        $this->info('Starting migration to unified_status...');

        $total = ScholarshipRecord::count();
        $this->info("Total records to process: {$total}");

        $bar = $this->output->createProgressBar($total);

        $records = ScholarshipRecord::all();
        $updated = 0;
        $errors = 0;

        foreach ($records as $record) {
            try {
                // Regenerate unified_status based on current approval_status and scholarship_status
                $unifiedStatus = ScholarshipRecord::generateUnifiedStatus(
                    $record->approval_status,
                    $record->scholarship_status
                );

                if ($record->unified_status !== $unifiedStatus) {
                    if (!$dryRun) {
                        $record->update(['unified_status' => $unifiedStatus]);
                    }
                    $updated++;
                }
            } catch (\Exception $e) {
                $this->error("Error processing record {$record->id}: {$e->getMessage()}");
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();

        $this->newLine();
        $this->info("Migration complete!");
        $this->info("Records updated: {$updated}");
        $this->info("Errors: {$errors}");

        if ($dryRun) {
            $this->warn('This was a dry-run. Run without --dry-run to apply changes.');
        }
    }
}
