<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\CombatLog\ChallengeModeRun;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\Tags\TagCategory;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Database\DungeonRoute\Dtos\SimilarDungeonRoute;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use App\Repositories\Interfaces\DungeonRoute\Dtos\DungeonRouteSearchFilter;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
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

    /**
     * @param  Collection<int, DungeonRoute>|null $dungeonRoutes
     * @return Collection<int, DungeonRoute>
     */
    public function getDungeonRoutesWithExpiredThumbnails(?Collection $dungeonRoutes = null): Collection
    {
        // ThumbnailService::queueThumbnailRefresh() reads dungeon and mappingVersion on every returned route
        return DungeonRoute::with(['dungeon', 'mappingVersion'])
            ->where('author_id', '>', '0')
            // Check if in queue, if so skip, unless the queue age is longer than keystoneguru.thumbnail.refresh_requeue_hours
            ->where(static function (EloquentBuilder $builder) {
                $builder->whereColumn('thumbnail_refresh_queued_at', '<', 'thumbnail_updated_at')
                    ->orWhere(static function (EloquentBuilder $builder) {
                        // If it is in the queue to be refreshed
                        $builder->whereColumn('thumbnail_refresh_queued_at', '>', 'thumbnail_updated_at')
                            ->whereDate('thumbnail_refresh_queued_at', '<', now()->subHours(config('keystoneguru.thumbnail.refresh_requeue_hours'))->toDateTimeString());
                    });
            })
            ->where(static function (EloquentBuilder $builder) {
                // Only if it's not already queued!
                $builder->whereColumn('updated_at', '>', 'thumbnail_updated_at')
                    ->whereDate('updated_at', '<', now()->subMinutes(config('keystoneguru.thumbnail.refresh_min'))->toDateTimeString());
            })
            ->when($dungeonRoutes, function (EloquentBuilder $builder) use ($dungeonRoutes) {
                // If we have a specific set of routes to refresh, only select those
                $builder->whereIn('id', $dungeonRoutes->pluck('id'));
            })->when(!$dungeonRoutes, function (EloquentBuilder $builder) {
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
     * @return Collection<string, Collection<int, WeeklyRoute>>
     */
    public function getWeeklyRoutes(?Dungeon $dungeon = null, ?Season $season = null): Collection
    {
        $season ??= $this->seasonService->getCurrentSeason();

        $weeklyRouteTags = config('keystoneguru.raider_io.weekly_route.tags');
        $tagCategoryId   = TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM];
        $raiderIOTeamId  = config('keystoneguru.raider_io.team_id');
        $tagsFilterFn    = function (HasMany|EloquentBuilder $query) use (
            $weeklyRouteTags,
            $tagCategoryId,
            $raiderIOTeamId
        ) {
            $query->whereIn('name', $weeklyRouteTags)
                ->where('tag_category_id', $tagCategoryId)
                ->where('context_id', $raiderIOTeamId);
        };

        return DungeonRoute::where('team_id', config('keystoneguru.raider_io.team_id'))
            ->with([ // @phpstan-ignore argument.type (Larastan passes concrete relation type; contravariant closure parameter is correct at runtime)
                // Everything the rendered route cards read - DungeonRoute no longer eager loads relations globally
                'author.iconfile',
                'dungeon',
                'affixes',
                'mappingVersion',
                'season.expansion',
                'thumbnails',
                'ratings',
                'tags' => $tagsFilterFn,
            ])
            ->withCount('favorites')
            ->when($dungeon, fn(EloquentBuilder $query) => $query->where('dungeon_id', $dungeon->id))
            ->whereRelation(
                'dungeon.seasonDungeons',
                'season_id',
                $season->id,
            )
            ->whereRelation('tags', $tagsFilterFn)
            ->orderBy('dungeon_id')
            ->get()
            ->groupBy(fn(DungeonRoute $route) => $route->dungeon->key)
            ->map(function (Collection $dungeonRoutes) use ($weeklyRouteTags) {
                /** @var Collection<int, WeeklyRoute> $result */
                $result = collect();
                foreach ($weeklyRouteTags as $key => $value) {
                    /** @var DungeonRoute|null $dungeonRoute */
                    $dungeonRoute = $dungeonRoutes->first(fn(DungeonRoute $route) => $route->tags->first()?->name === $value);
                    if ($dungeonRoute) {
                        $result->push(new WeeklyRoute($key, $dungeonRoute));
                    }
                }

                return $result;
            });
    }

    /**
     * @return Collection<int, SimilarDungeonRoute>
     */
    public function findSimilarRoutes(DungeonRoute $dungeonRoute, int $limit = 5): Collection
    {
        $limit = 5;

        $candidateRoutesSql = $this->findRoutesBuilder(
            new DungeonRouteSearchFilter($dungeonRoute->mappingVersion),
            $dungeonRoute,
        )->selectRaw('id, public_key, title, popularity')
            // Need to add a popularity check so that we don't get all garbage routes as suggestions
            ->where('popularity', '>', 0);

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
                    ' . $candidateRoutesSql->toSql() . '
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

    /**
     * @return Collection<int, DungeonRoute>
     */
    public function findRoutes(DungeonRouteSearchFilter $filter): Collection
    {
        /** @var Collection<int, DungeonRoute> $result */
        $result = $this->findRoutesBuilder($filter)
            ->limit(5)
            ->get();

        return $result;
    }

    public function findCombatLogRouteByPublicKey(?string $publicKey): ?DungeonRoute
    {
        if ($publicKey === null) {
            return null;
        }

        $dungeonRoute = DungeonRoute::where('public_key', $publicKey)->first();
        if ($dungeonRoute === null) {
            return null;
        }

        // ChallengeModeRun lives in a different connection, so we need to use the model directly
        $challengeModeRun = ChallengeModeRun::where('dungeon_route_id', $dungeonRoute->id)->first();

        if ($challengeModeRun === null) {
            return null;
        }

        return $dungeonRoute;
    }

    /**
     * @return EloquentBuilder<DungeonRoute>
     */
    private function findRoutesBuilder(
        DungeonRouteSearchFilter $filter,
        ?DungeonRoute            $excludeDungeonRoute = null,
    ): EloquentBuilder {
        $query = DungeonRoute::query()
            // Everything the rendered search result cards read - DungeonRoute no longer eager loads relations globally
            ->with([
                'author.iconfile',
                'dungeon',
                'affixes',
                'mappingVersion',
                'season.expansion',
                'thumbnails',
                'ratings',
            ])
            ->when(
                $filter->username !== null,
                fn(EloquentBuilder $query) => $query->whereRelation('author', 'name', 'LIKE', '%' . $filter->username . '%'),
            )
            ->when($excludeDungeonRoute !== null, fn(EloquentBuilder $query) => $query->where('id', '!=', $excludeDungeonRoute->id))
            ->when($filter->title !== null, fn(EloquentBuilder $query) => $query->where('title', 'like', '%' . $filter->title . '%'))
            ->when($filter->minKeyLevel !== null, fn(EloquentBuilder $query) => $query->where('level_min', '>=', $filter->minKeyLevel))
            ->when($filter->maxKeyLevel !== null, fn(EloquentBuilder $query) => $query->where('level_max', '<=', $filter->maxKeyLevel))
            ->where('mapping_version_id', $filter->mappingVersion->id)
            ->where('published_state_id', PublishedState::ALL[PublishedState::WORLD])
            ->whereNull('clone_of')
            ->whereNull('expires_at')
            ->orderByDesc('popularity');

        if ($filter->includedEnemies !== null) {
            $pairs = $this->parseEnemyPairs($filter->includedEnemies);

            if (!empty($pairs)) {
                $query->whereIn('id', $this->buildDungeonRouteEnemyPairsSubquery(
                    pairs: $pairs,
                    mappingVersionId: $filter->mappingVersion->id,
                    requireAllPairs: true,
                ));
            }
        }

        if ($filter->excludedEnemies !== null) {
            $pairs = $this->parseEnemyPairs($filter->excludedEnemies);

            if (!empty($pairs)) {
                $query->whereNotIn('id', $this->buildDungeonRouteEnemyPairsSubquery(
                    pairs: $pairs,
                    mappingVersionId: $filter->mappingVersion->id,
                    requireAllPairs: false,
                ));
            }
        }

        return $query;
    }

    /**
     * AI function:
     *
     * Builds a subquery that returns dungeon_route_id values matching any of the given NPC/MDT pairs.
     *
     * If $requireAllPairs is true, the subquery only returns routes that contain every requested pair.
     * If $requireAllPairs is false, the subquery returns routes that contain at least one requested pair.
     *
     * @param array<int, array{0:int,1:int}> $pairs
     */
    private function buildDungeonRouteEnemyPairsSubquery(
        array $pairs,
        int   $mappingVersionId,
        bool  $requireAllPairs,
    ): QueryBuilder {
        $pairCount = count($pairs);

        $subQuery = DB::table('kill_zones as kz')
            ->select('kz.dungeon_route_id')
            ->join('kill_zone_enemies as kze', 'kze.kill_zone_id', '=', 'kz.id')
            ->join('dungeon_routes as dr', 'dr.id', '=', 'kz.dungeon_route_id')
            ->where('dr.mapping_version_id', $mappingVersionId)
            ->where(function ($query) use ($pairs) {
                foreach ($pairs as [$npcId, $mdtId]) {
                    $query->orWhere(function ($pairQuery) use ($npcId, $mdtId) {
                        $pairQuery->where('kze.npc_id', $npcId)
                            ->where('kze.mdt_id', $mdtId);
                    });
                }
            })
            ->groupBy('kz.dungeon_route_id');

        if ($requireAllPairs) {
            $subQuery->havingRaw(
                'COUNT(DISTINCT CONCAT(kze.npc_id, ":", kze.mdt_id)) = ?',
                [$pairCount],
            );
        }

        return $subQuery;
    }

    /**
     * @param  array<int, string>             $values like ["165529;5", ...]
     * @return array<int, array{0:int,1:int}> like [[165529, 5], ...]
     */
    /**
     * @param  array<int, int|string>      $values
     * @return array<int, array<int, int>>
     */
    private function parseEnemyPairs(array $values): array
    {
        $result = [];

        foreach ($values as $value) {
            if (!is_string($value)) {
                continue;
            }

            $parts = explode(';', $value, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $npcId = (int)$parts[0];
            $mdtId = (int)$parts[1];

            $result[] = [
                $npcId,
                $mdtId,
            ];
        }

        return $result;
    }
}
