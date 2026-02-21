<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\Tags\TagCategory;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Database\DungeonRoute\Dtos\SimilarDungeonRoute;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Random\RandomException;

class DungeonRouteRepository extends DatabaseRepository implements DungeonRouteRepositoryInterface
{
    public function __construct(
        private readonly SeasonServiceInterface $seasonService,
    ) {
        parent::__construct(DungeonRoute::class);
    }

    public function generateRandomPublicKey(): string
    {
        try {
            return DungeonRoute::generateRandomPublicKey();
        } catch (RandomException) {
            return 'RandomException!';
        }
    }

    public function getDungeonRoutesWithExpiredThumbnails(?Collection $dungeonRoutes = null): Collection
    {
        /** @var Collection<DungeonRoute> $routes */
        return DungeonRoute::where('author_id', '>', '0')
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
            ->when($dungeonRoutes, function (Builder $builder) use ($dungeonRoutes) {
                // If we have a specific set of routes to refresh, only select those
                $builder->whereIn('id', $dungeonRoutes->pluck('id'));
            })->when(!$dungeonRoutes, function (Builder $builder) {
                // Otherwise, only select routes that have been recently updated/viewed/accessed
                $builder->where('popularity', '>', 0);
            })
            // Published routes get priority! This is only really relevant initially while processing the thumbnail queue
            ->orderBy('published_state_id', 'desc')
            // Newest first
            ->orderBy('id', 'desc')
            // Limit the amount of routes at a time, do not overflow the queue since we cannot process more anyway
            ->limit(config('keystoneguru.thumbnail.refresh_outdated_count'))
            ->get();
    }

    /**
     * @return Collection<string, Collection<WeeklyRoute>>
     */
    public function getWeeklyRoutes(?Dungeon $dungeon = null): Collection
    {
        $currentSeason = $this->seasonService->getCurrentSeason();

        $weeklyRouteTags = config('keystoneguru.raider_io.weekly_route.tags');
        $tagCategoryId   = TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM];
        $raiderIOTeamId  = config('keystoneguru.raider_io.team_id');
        $tagsFilterFn    = function (HasMany|Builder $query) use (
            $weeklyRouteTags,
            $tagCategoryId,
            $raiderIOTeamId
        ) {
            $query->whereIn('name', $weeklyRouteTags)
                ->where('tag_category_id', $tagCategoryId)
                ->where('context_id', $raiderIOTeamId);
        };

        return DungeonRoute::where('team_id', config('keystoneguru.raider_io.team_id'))
            ->with([
                'author',
                'dungeon',
                'tags' => $tagsFilterFn,
            ])
            ->when($dungeon, fn(Builder $query) => $query->where('dungeon_id', $dungeon->id))
            ->whereRelation(
                'dungeon.seasonDungeons',
                'season_id',
                $currentSeason->id,
            )
            ->whereRelation('tags', $tagsFilterFn)
            ->orderBy('dungeon_id')
            ->get()
            ->groupBy(fn(DungeonRoute $route) => $route->dungeon->key)
            ->map(function (Collection $dungeonRoutes) use ($weeklyRouteTags) {
                $result = collect();
                foreach ($weeklyRouteTags as $key => $value) {
                    /** @var DungeonRoute $dungeonRoute */
                    $dungeonRoute = $dungeonRoutes->first(fn(DungeonRoute $route) => $route->tags->first()?->name === $value);
                    if ($dungeonRoute) {
                        $result->push(new WeeklyRoute($key, $dungeonRoute));
                    }
                }

                return $result;
            });
    }

    public function findSimilarRoutes(DungeonRoute $dungeonRoute, int $limit = 5): Collection
    {
        $limit = 5;

        $sql = '
            SELECT
                dr2.id,
                dr2.popularity,
                originalRouteEnemies.total,
                COUNT(*) / originalRouteEnemies.total as ratio
            FROM
                (
                    SELECT DISTINCT kze.npc_id, kze.mdt_id
                    FROM kill_zones kz
                             JOIN kill_zone_enemies kze ON kze.kill_zone_id = kz.id
                    WHERE kz.dungeon_route_id = :routeIdBase
                ) base
                    JOIN
                (
                    SELECT COUNT(*) as total
                    FROM (
                             SELECT DISTINCT kze.npc_id, kze.mdt_id
                             FROM kill_zones kz
                                      JOIN kill_zone_enemies kze ON kze.kill_zone_id = kz.id
                             WHERE kz.dungeon_route_id = :routeIdCnt
                               AND kze.npc_id IS NOT NULL
                               AND kze.mdt_id IS NOT NULL
                         ) as cnt
                ) originalRouteEnemies
                    JOIN
                (
                    SELECT id, public_key, title, popularity
                    FROM dungeon_routes
                    WHERE mapping_version_id = :mappingVersionId
                      AND published_state_id = :publishedState
                      AND id <> :routeIdExclude
                      AND (clone_of IS NULL OR clone_of <> :publicKey)
                      AND popularity > 0
                    ORDER BY popularity DESC
                ) dr2
                    JOIN kill_zones kz2 ON kz2.dungeon_route_id = dr2.id
                    JOIN kill_zone_enemies kze2
                         ON kze2.kill_zone_id = kz2.id
                        AND kze2.npc_id = base.npc_id
                        AND kze2.mdt_id = base.mdt_id
            GROUP BY dr2.id, dr2.public_key, dr2.title, originalRouteEnemies.total, dr2.popularity
            HAVING ratio < 1
            ORDER BY ratio DESC, dr2.popularity DESC
            LIMIT ' . $limit . ';
        ';

        $rows = collect(DB::select($sql, [
            'routeIdBase'      => $dungeonRoute->id,
            'routeIdCnt'       => $dungeonRoute->id,
            'routeIdExclude'   => $dungeonRoute->id,
            'mappingVersionId' => $dungeonRoute->mapping_version_id,
            'publishedState'   => PublishedState::ALL[PublishedState::WORLD],
            'publicKey'        => $dungeonRoute->public_key,
        ]))->keyBy('id');

        $ids = $rows->pluck('id')->map(fn($v) => (int)$v)->values();

        // Use Eloquent to fetch the routes and their relationships
        return DungeonRoute::query()
            ->whereIn('id', $ids)
            ->orderByRaw('FIELD(id, ' . implode(',', $ids->toArray()) . ')')
            ->get()
            ->map(function (DungeonRoute $route) use ($rows, $dungeonRoute) {
                /** @var object{ratio: float} $row */
                $row = $rows->get($route->id);

                return new SimilarDungeonRoute($row->ratio, $dungeonRoute);
            });
    }
}
