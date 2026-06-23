<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Season;
use App\Repositories\BaseRepositoryInterface;
use App\Repositories\Database\DungeonRoute\Dtos\SimilarDungeonRoute;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use App\Repositories\Interfaces\DungeonRoute\Dtos\DungeonRouteSearchFilter;
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
     * @return Collection<int, DungeonRoute>
     */
    public function findRoutes(DungeonRouteSearchFilter $filter): Collection;

    public function findCombatLogRouteByPublicKey(?string $publicKey): ?DungeonRoute;
}
