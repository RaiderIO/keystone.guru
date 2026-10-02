<?php

namespace App\Console\Commands\Scheduler\PageView;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Models\PageView;
use App\Repositories\Interfaces\PageViewCountRepositoryInterface;
use Illuminate\Support\Carbon;

class Prune extends SchedulerCommand
{
    protected $signature = 'page-views:prune';

    protected $description = 'Aggregates completed days of page views into daily counts, then deletes page view records older than the configured retention period';

    public function handle(PageViewCountRepositoryInterface $pageViewCountRepository): int
    {
        return $this->trackTime(function () use ($pageViewCountRepository): void {
            $retentionDays = config('keystoneguru.page_views.retention_days');

            $this->aggregateCompletedDays($pageViewCountRepository, $retentionDays);

            $cutoff       = now()->subDays($retentionDays);
            $batchSize    = 10000;
            $totalDeleted = 0;

            do {
                $deleted = PageView::query()
                    ->where('created_at', '<', $cutoff)
                    ->limit($batchSize)
                    ->delete();

                $totalDeleted += $deleted;
            } while ($deleted === $batchSize);

            $this->info(sprintf('Pruned %d page view records older than %d days.', $totalDeleted, $retentionDays));
        });
    }

    /**
     * Aggregates every completed day since the last aggregated one. Days older than the retention window are skipped:
     * the prune may already have deleted part of them, and a partial count would be wrong.
     */
    private function aggregateCompletedDays(PageViewCountRepositoryInterface $pageViewCountRepository, int $retentionDays): void
    {
        $from           = Carbon::today()->subDays($retentionDays - 1);
        $latestViewedOn = $pageViewCountRepository->getLatestViewedOn();
        if ($latestViewedOn !== null && $latestViewedOn->gte($from)) {
            $from = $latestViewedOn->copy()->addDay();
        }

        $yesterday       = Carbon::yesterday();
        $aggregatedDays  = 0;
        $aggregatedCount = 0;
        for ($day = $from->copy(); $day->lte($yesterday); $day->addDay()) {
            $aggregatedCount += $pageViewCountRepository->aggregateDay($day);
            $aggregatedDays++;
        }

        $this->info(sprintf('Aggregated %d page view counts over %d days.', $aggregatedCount, $aggregatedDays));
    }
}
