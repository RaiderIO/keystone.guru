<?php

namespace App\Console\Commands\Scheduler\Thumbnail;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Service\DungeonRoute\ThumbnailServiceInterface;

class ExpireInactiveThumbnails extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'thumbnail:expireinactive {--dry-run : Only report how many routes would be expired} {--limit= : The maximum amount of routes to expire, defaults to keystoneguru.thumbnail.expire_inactive_count}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes the standard and front page thumbnails of routes that were neither edited nor displayed recently. They are rendered again when the route is next displayed.';

    public function handle(ThumbnailServiceInterface $thumbnailService): int
    {
        $dryRun = (bool)$this->option('dry-run');
        $limit  = $this->option('limit') === null ? null : (int)$this->option('limit');

        return $this->trackTime(function () use ($thumbnailService, $dryRun, $limit) {
            $count = $thumbnailService->expireInactiveThumbnails($limit, $dryRun);

            $this->info(sprintf(
                $dryRun ? 'Would expire the thumbnails of %d inactive routes' : 'Expired the thumbnails of %d inactive routes',
                $count,
            ));

            return 0;
        });
    }
}
