<?php

namespace App\Console\Commands;

use App\Models\ScholarshipRecord;
use App\Services\AcademicRecordSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillAcademicEnrollments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'academic-records:backfill {--fix : Automatically sync all unmapped records without prompting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync scholarship records that have no academic_enrollments row yet (has_academic_record shows false despite a scholarship record existing)';

    public function handle(AcademicRecordSyncService $academicRecordSyncService)
    {
        $fix = $this->option('fix');

        $this->info('🔍 Checking for scholarship records missing an academic enrollment mapping...');

        $unmappedIds = ScholarshipRecord::query()
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('academic_enrollment_term_record_maps as maps')
                    ->whereColumn('maps.scholarship_record_id', 'scholarship_records.id');
            })
            ->pluck('id');

        $count = $unmappedIds->count();

        if ($count === 0) {
            $this->info('✅ All scholarship records are already mapped. Nothing to backfill.');
            return 0;
        }

        $this->warn("⚠️  Found {$count} unmapped scholarship record(s)");

        if (!$fix && !$this->confirm("\n🔧 Sync all {$count} record(s) into academic_enrollments now?")) {
            $this->info('❌ Backfill cancelled.');
            return 0;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $synced = 0;
        $errors = [];

        ScholarshipRecord::query()
            ->whereIn('id', $unmappedIds)
            ->chunkById(200, function ($records) use ($academicRecordSyncService, &$bar, &$synced, &$errors) {
                foreach ($records as $record) {
                    try {
                        $academicRecordSyncService->syncScholarshipRecord($record);
                        $synced++;
                    } catch (\Exception $e) {
                        $errors[] = ['id' => $record->id, 'error' => $e->getMessage()];
                    }
                    $bar->advance();
                }
            });

        $bar->finish();

        $this->info("\n\n✅ Backfill complete!");
        $this->line("📊 Synced: {$synced} record(s)");

        if (!empty($errors)) {
            $this->error('❌ Errors: ' . count($errors) . ' record(s)');
            foreach ($errors as $error) {
                $this->line("  - ID {$error['id']}: {$error['error']}");
            }
            return 1;
        }

        return 0;
    }
}
