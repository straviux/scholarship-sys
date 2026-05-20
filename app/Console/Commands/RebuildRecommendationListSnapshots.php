<?php

namespace App\Console\Commands;

use App\Models\RecommendationList;
use App\Services\RecommendationListService;
use Illuminate\Console\Command;

class RebuildRecommendationListSnapshots extends Command
{
    protected $signature = 'recommendation-lists:rebuild-snapshots {--id=* : Only rebuild specific recommendation list IDs}';

    protected $description = 'Rebuild records_snapshot for existing recommendation lists (e.g. to backfill new fields like municipality, remarks).';

    public function handle(RecommendationListService $service): int
    {
        $query = RecommendationList::query();

        $ids = $this->option('id');
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $lists = $query->get();
        $this->info("Rebuilding snapshots for {$lists->count()} recommendation list(s)...");

        $progress = $this->output->createProgressBar($lists->count());
        $progress->start();

        $failures = [];
        foreach ($lists as $list) {
            try {
                $service->rebuildSnapshot($list);
            } catch (\Throwable $e) {
                $failures[] = "  [{$list->id}] {$list->list_number}: " . $e->getMessage();
            }
            $progress->advance();
        }

        $progress->finish();
        $this->newLine(2);

        if (!empty($failures)) {
            $this->warn('Some lists failed to rebuild:');
            foreach ($failures as $f) {
                $this->line($f);
            }
            return self::FAILURE;
        }

        $this->info('All snapshots rebuilt successfully.');
        return self::SUCCESS;
    }
}
