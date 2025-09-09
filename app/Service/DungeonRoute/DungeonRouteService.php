<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\DungeonRoute\Logging\DungeonRouteServiceLoggingInterface;
use Exception;
use Illuminate\Support\Facades\DB;

class DungeonRouteService implements DungeonRouteServiceInterface
{
    public function __construct(
        private readonly DungeonRouteRepositoryInterface     $dungeonRouteRepository,
        private readonly ThumbnailServiceInterface           $thumbnailService,
        private readonly DungeonRouteServiceLoggingInterface $log
    ) {
    }

    public function updatePopularity(): int
    {
        $updatedRouteCount = 0;
        try {
            $this->log->updatePopularityStart();

            $updatedRouteCount = DB::update('
            UPDATE dungeon_routes, (
                SELECT model_id, count(0) as views
                FROM page_views
                WHERE page_views.model_class = :modelClass
                AND page_views.created_at > :popularityDate
                GROUP BY page_views.model_id
            ) as page_views,
            (
                SELECT MAX(id) as ids
                FROM mapping_versions
                GROUP BY mapping_versions.dungeon_id
                /**
                  @TODO #2933 Where game version is the same as the dungeon route\'s mapping version?
                 */
            ) as latest_mapping_version_ids
            SET dungeon_routes.popularity = page_views.views
            /*
                This will calculate a number between 1 and 0 depending on the age of the route. A new route will generate 1.
                A route at popularityFalloffDays days will produce 0. This will ensure that old routes fall off the
                popularity board over time and the overview stays fresh
            */
                * GREATEST(0, (1 - DATEDIFF(NOW(), dungeon_routes.updated_at) / :popularityFalloffDays))
            /*
                Adds a penalty if your route does not use the latest mapping version for your dungeon
             */
                * IF(FIND_IN_SET(dungeon_routes.mapping_version_id, latest_mapping_version_ids.ids) > 1, 1, :outOfDateMappingVersionPenalty)
            WHERE dungeon_routes.id = page_views.model_id
            /*
               Only public routes can have their popularity updated for performance reasons
             */
                AND dungeon_routes.published_state_id IN (:publishedStates)
            /*
                If your route is cloned, it cannot show up in any popularity pages
             */
                AND dungeon_routes.clone_of IS NULL
        ', [
                'modelClass'                     => DungeonRoute::class,
                'popularityDate'                 => now()->subDays(config('keystoneguru.discover.service.popular_days'))->toDateTimeString(),
                'popularityFalloffDays'          => config('keystoneguru.discover.service.popular_falloff_days'),
                'outOfDateMappingVersionPenalty' => config('keystoneguru.discover.service.popular_out_of_date_mapping_version_penalty'),
                'publishedStates'                => implode(',', [PublishedState::ALL[PublishedState::WORLD]]),
            ]);
        } finally {
            $this->log->updatePopularityEnd($updatedRouteCount);
        }

        return $updatedRouteCount;
    }

    public function updateRating(): int
    {
        $updatedRouteCount = 0;
        try {
            $this->log->updateRatingStart();

            $updatedRouteCount = DB::update('
            UPDATE dungeon_routes, (
                SELECT dungeon_route_id, truncate(avg(dungeon_route_ratings.rating), 1) as ratingAvg, count(dungeon_route_ratings.rating) as ratingCount
                            FROM dungeon_route_ratings
                            GROUP BY dungeon_route_ratings.dungeon_route_id
                ) as ratings
            SET dungeon_routes.rating = ratings.ratingAvg, dungeon_routes.rating_count = ratings.ratingCount
            WHERE dungeon_routes.id = ratings.dungeon_route_id
        ');
        } finally {
            $this->log->updateRatingEnd($updatedRouteCount);
        }

        return $updatedRouteCount;
    }

    public function refreshOutdatedThumbnails(): int
    {
        $routes = collect();

        $sendResult = true;
        try {
            $this->log->refreshOutdatedThumbnailsStart();

            $dungeonRoutesWithExpiredThumbnails = $this->dungeonRouteRepository->getDungeonRoutesWithExpiredThumbnails();

            // All routes that come from the above will need their thumbnails regenerated, loop over them and queue the jobs at once
            foreach ($dungeonRoutesWithExpiredThumbnails as $dungeonRoute) {
                $sendResult = $this->thumbnailService->queueThumbnailRefresh($dungeonRoute) && $sendResult;
            }
        } finally {
            $this->log->refreshOutdatedThumbnailsEnd($routes->count(), $sendResult);
        }

        return 0;
    }

    public function deleteExpiredDungeonRoutes(): int
    {
        $deletedRouteCount = 0;
        try {
            $this->log->deleteOutdatedDungeonRoutesStart();

            $dungeonRoutes = DungeonRoute::with([
                'brushlines',
                'paths',
                'killZones',
                'livesessions',
            ])
                ->whereRaw('expires_at < NOW()')
                ->where('expires_at', '!=', 0)
                ->whereNotNull('expires_at')
                ->get();

            // Retrieve all routes and then delete them
            foreach ($dungeonRoutes as $dungeonRoute) {
                /** @var $dungeonRoute DungeonRoute */
                try {
                    $dungeonRoute->delete();
                    $deletedRouteCount++;
                } catch (Exception $ex) {
                    $this->log->deleteOutdatedDungeonRouteException($dungeonRoute->id, $ex);
                }
            }

        } finally {
            $this->log->deleteOutdatedDungeonRoutesEnd($deletedRouteCount);
        }

        return $deletedRouteCount;
    }

    public function touchRoutesForTeam(int $teamId): int
    {
        $updatedRouteCount = 0;
        try {
            $this->log->touchRoutesForTeamStart($teamId);

            $updatedRouteCount = DungeonRoute::where('team_id', $teamId)->update(['updated_at' => now()]);
        } finally {
            $this->log->touchRoutesForTeamEnd($teamId, $updatedRouteCount);
        }

        return $updatedRouteCount;
    }

}
