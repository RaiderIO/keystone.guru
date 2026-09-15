<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Season;
use App\Repositories\BaseRepositoryInterface;
use App\Repositories\Database\DungeonRoute\Dtos\KillZoneEnemyForces;
use App\Repositories\Database\DungeonRoute\Dtos\SimilarDungeonRoute;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use App\Repositories\Interfaces\DungeonRoute\Dtos\DungeonRouteSearchFilter;
use Closure;
use Illuminate\Support\Collection;

/**
 * @method DungeonRoute                  create(array<string, mixed> $attributes)
 * @method DungeonRoute|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRoute                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRoute                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                          save(DungeonRoute $model)
 * @method bool                          update(DungeonRoute $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                          delete(DungeonRoute $model)
 * @method Collection<int, DungeonRoute> all()
 * @method bool                          exists(array<string, mixed> $columns)
 */
interface DungeonRouteRepositoryInterface extends BaseRepositoryInterface
{
    public function generateRandomPublicKey(): string;

    /**
     * @param  Collection<int, DungeonRoute>|null $dungeonRoutes
     * @return Collection<int, DungeonRoute>
     */
    public function getDungeonRoutesWithExpiredThumbnails(?Collection $dungeonRoutes = null): Collection;

    /** @return Collection<string, Collection<int, WeeklyRoute>> */
    public function getWeeklyRoutes(?Dungeon $dungeon = null, ?Season $season = null): Collection;

    /** @return Collection<int, SimilarDungeonRoute> */
    public function findSimilarRoutes(DungeonRoute $dungeonRoute, int $limit = 5): Collection;

    /**
     * Gets the summed enemy forces for each kill zone (pull) in the given route, ordered by the kill
     * zone's index, along with whether that pull contains a boss. Used to render the "route
     * fingerprint" bar graph. Single aggregate query mirroring the enemy-forces accounting of
     * DungeonRoute::getEnemyForces() but grouped per pull instead of per route.
     *
     * @return Collection<int, KillZoneEnemyForces>
     */
    public function getEnemyForcesPerKillZone(DungeonRoute $dungeonRoute): Collection;

    /**
     * Batched variant of {@see self::getEnemyForcesPerKillZone()} - fetches the per-pull enemy forces
     * for an entire collection of routes (e.g. a leaderboard page) in O(1) queries instead of one
     * query per route, keyed by dungeon route id.
     *
     * @param  Collection<int, DungeonRoute>                         $dungeonRoutes
     * @return Collection<int, Collection<int, KillZoneEnemyForces>>
     */
    public function getEnemyForcesPerKillZoneForRoutes(Collection $dungeonRoutes): Collection;

    /**
     * @return Collection<int, DungeonRoute>
     */
    public function findRoutes(DungeonRouteSearchFilter $filter): Collection;

    public function findCombatLogRouteByPublicKey(?string $publicKey): ?DungeonRoute;

    /**
     * Chunks all dungeon routes of the given season and/or dungeons, passing each chunk to the given callback.
     *
     * @param Collection<int, int>|null                    $dungeonIds
     * @param Closure(Collection<int, DungeonRoute>): void $callback
     */
    public function chunkBySeasonAndDungeonIds(?Season $season, ?Collection $dungeonIds, int $chunkSize, Closure $callback): void;
}
