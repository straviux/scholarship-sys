<?php

namespace App\Console\Commands;

use App\Models\RecommendationList;
use App\Services\RecommendationListService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class RenumberRecommendationLists extends Command
{
    protected $signature = 'recommendation-lists:renumber {--force : Apply the changes instead of only previewing them}';

    protected $description = 'Backfill existing recommendation lists (e.g. old "RFA-..." numbers) onto the per-program prefix scheme (EFA/MED/TEC/BAR/...), each with its own sequence.';

    public function handle(RecommendationListService $service): int
    {
        $lists = RecommendationList::withTrashed()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($lists->isEmpty()) {
            $this->info('No recommendation lists found.');
            return self::SUCCESS;
        }

        $sequences = [];
        $changes = [];

        foreach ($lists as $list) {
            $shortnames = collect($list->records_snapshot ?? [])->pluck('program.shortname');
            $prefix = $service->resolveListNumberPrefix($shortnames);

            $nextSequence = ($sequences[$prefix] ?? 0) + 1;
            $sequences[$prefix] = $nextSequence;

            $newListNumber = sprintf('%s-%s-%04d', $prefix, $list->created_at->format('Ymd'), $nextSequence);

            if ($newListNumber !== $list->list_number) {
                $changes[] = [$list, $newListNumber];
            }
        }

        if (empty($changes)) {
            $this->info('All recommendation lists already match the per-program prefix scheme.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Old List No.', 'New List No.'],
            collect($changes)->map(fn($change) => [$change[0]->id, $change[0]->list_number, $change[1]])->all()
        );

        if (!$this->option('force')) {
            $this->warn(count($changes) . ' list(s) would be renumbered. Re-run with --force to apply.');
            return self::SUCCESS;
        }

        /** @var array{0: RecommendationList, 1: string} $change */
        foreach ($changes as $change) {
            [$list, $newListNumber] = $change;
            $list->list_number = $newListNumber;
            $list->save();
        }

        $this->info(count($changes) . ' recommendation list(s) renumbered.');
        return self::SUCCESS;
    }
}
