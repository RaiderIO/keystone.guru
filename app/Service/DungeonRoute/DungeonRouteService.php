<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Service\DungeonRoute\Logging\DungeonRouteServiceLoggingInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DungeonRouteService implements DungeonRouteServiceInterface
{
    public function __construct(
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

        try {
            $this->log->refreshOutdatedThumbnailsStart();

            /** @var Collection<DungeonRoute> $routes */
            $routes = DungeonRoute::where('author_id', '>', '0')
                // Check if in queue, if so skip, unless the queue age is longer than keystoneguru.thumbnail.refresh_requeue_hours
                ->where(static function (Builder $builder) {
                    $builder->whereColumn('thumbnail_refresh_queued_at', '<', 'thumbnail_updated_at')
                        ->orWhere(static function (Builder $builder) {
                            // If it is in the queue to be refreshed
                            $builder->whereColumn('thumbnail_refresh_queued_at', '>', 'thumbnail_updated_at')
                                ->whereDate('thumbnail_refresh_queued_at', '<', now()->subHours(config('keystoneguru.thumbnail.refresh_requeue_hours'))->toDateTimeString());
                        });
                })
                ->where(static function (Builder $builder) {
                    // Only if it's not already queued!
                    $builder->whereColumn('updated_at', '>', 'thumbnail_updated_at')
                        ->whereDate('updated_at', '<', now()->subMinutes(config('keystoneguru.thumbnail.refresh_min'))->toDateTimeString());
                })
                // But only routes that have been recently updated/viewed/accessed
                ->where('popularity', '>', 0)
                // Published routes get priority! This is only really relevant initially while processing the thumbnail queue
                ->orderBy('published_state_id', 'desc')
                // Newest first
                ->orderBy('id', 'desc')
                // Limit the amount of routes at a time, do not overflow the queue since we cannot process more anyway
                ->limit(config('keystoneguru.thumbnail.refresh_outdated_count'))
                ->get();

            // All routes that come from the above will need their thumbnails regenerated, loop over them and queue the jobs at once
            $this->thumbnailService->queueThumbnailRefreshIfMissing($routes);
        } finally {
            $this->log->refreshOutdatedThumbnailsEnd($routes->count());
        }

        return 0;
    }

    public function deleteExpiredDungeonRoutes(): int
    {
        $deletedRouteCount = 0;
        try {
            $this->log->deleteOutdatedDungeonRoutesStart();

            $dungeonRoutes = DungeonRoute::with(['brushlines', 'paths', 'killZones', 'livesessions'])
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
