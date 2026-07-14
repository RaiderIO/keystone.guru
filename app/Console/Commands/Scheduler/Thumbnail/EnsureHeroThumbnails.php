<?php

namespace App\Console\Commands\Scheduler\Thumbnail;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Team;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;

class EnsureHeroThumbnails extends SchedulerCommand
{
    /**
     * The number of top community routes per dungeon that get a hero thumbnail.
     */
    private const int TOP_ROUTES_PER_DUNGEON = 3;

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
        SeasonServiceInterface          $seasonService,
        DiscoverServiceInterface        $discoverService,
        DungeonRouteRepositoryInterface $dungeonRouteRepository,
        ThumbnailServiceInterface       $thumbnailService,
    ): int {
        return $this->trackTime(function () use ($seasonService, $discoverService, $dungeonRouteRepository, $thumbnailService) {
            $heroRoutes = $this->getHeroRoutes($seasonService, $discoverService, $dungeonRouteRepository);

            $queued = 0;
            foreach ($heroRoutes as $dungeonRoute) {
                if ($thumbnailService->queueHeroThumbnailRefresh($dungeonRoute)) {
                    $queued++;
                }
            }

            $this->info(sprintf('Queued hero thumbnails for %d of %d candidate routes', $queued, $heroRoutes->count()));

            return 0;
        });
    }

    /**
     * The routes that appear as heroes on the discovery pages: every Raider.IO weekly route plus the top
     * community routes of each current-season dungeon, deduplicated by id.
     *
     * @return Collection<int, DungeonRoute>
     */
    private function getHeroRoutes(
        SeasonServiceInterface          $seasonService,
        DiscoverServiceInterface        $discoverService,
        DungeonRouteRepositoryInterface $dungeonRouteRepository,
    ): Collection {
        $currentSeason = $seasonService->getCurrentSeason();
        if ($currentSeason === null) {
            return collect();
        }

        /** @var Collection<int, DungeonRoute> $heroRoutes */
        $heroRoutes = collect();

        // The Raider.IO weekly routes (grouped by dungeon key), which are always shown as heroes
        $dungeonRouteRepository->getWeeklyRoutes()
            ->flatten()
            ->each(function (WeeklyRoute $weeklyRoute) use ($heroRoutes) {
                if ($weeklyRoute->dungeonRoute !== null) {
                    $heroRoutes->push($weeklyRoute->dungeonRoute);
                }
            });

        // The top community routes per dungeon, mirroring the discovery page's popularity ordering
        $discoverService = $discoverService
            ->excludeTeam(Team::getRaiderIOTeam())
            ->withSeason($currentSeason)
            ->withLimit(self::TOP_ROUTES_PER_DUNGEON);

        foreach ($currentSeason->dungeons as $dungeon) {
            $discoverService->popularByDungeon($dungeon)
                ->take(self::TOP_ROUTES_PER_DUNGEON)
                ->each(fn(DungeonRoute $dungeonRoute) => $heroRoutes->push($dungeonRoute));
        }

        return $heroRoutes->unique('id')->values();
    }
}
