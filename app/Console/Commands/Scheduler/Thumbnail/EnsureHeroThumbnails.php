<?php

namespace App\Console\Commands\Scheduler\Thumbnail;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Season\SeasonServiceInterface;

class EnsureHeroThumbnails extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'thumbnail:ensureheroes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensures the wide hero-band thumbnail exists and is fresh for the routes shown as heroes on the discovery pages (Raider.IO weekly routes + top community routes per dungeon).';

    public function handle(
        SeasonServiceInterface    $seasonService,
        DiscoverServiceInterface  $discoverService,
        ThumbnailServiceInterface $thumbnailService,
    ): int {
        return $this->trackTime(function () use ($seasonService, $discoverService, $thumbnailService) {
            $currentSeason = $seasonService->getCurrentSeason();
            if ($currentSeason === null) {
                $this->info('No current season; nothing to do');

                return 0;
            }

            $heroRoutes = $discoverService->heroRoutes($currentSeason);

            $queued = 0;
            foreach ($heroRoutes as $dungeonRoute) {
                if ($thumbnailService->queueThumbnailRefresh($dungeonRoute, false, DungeonRouteThumbnailVariant::Hero)) {
                    $queued++;
                }
            }

            $this->info(sprintf('Queued hero thumbnails for %d of %d candidate routes', $queued, $heroRoutes->count()));

            return 0;
        });
    }
}
