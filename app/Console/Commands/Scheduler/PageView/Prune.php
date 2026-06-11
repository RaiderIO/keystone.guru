<?php

namespace App\Console\Commands\Scheduler\PageView;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Models\PageView;

class Prune extends SchedulerCommand
{
    protected $signature = 'page-views:prune';

    protected $description = 'Deletes page view records older than the configured retention period';

    public function handle(): int
    {
        return $this->trackTime(function (): void {
            $retentionDays = config('keystoneguru.page_views.retention_days');
            $cutoff        = now()->subDays($retentionDays);
            $batchSize     = 10000;
            $totalDeleted  = 0;

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
}
