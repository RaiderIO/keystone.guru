<?php

namespace App\Repositories\Stub\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Season;
use App\Models\User;
use App\Repositories\Database\DungeonRoute\Dtos\SimilarDungeonRoute;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use App\Repositories\Interfaces\DungeonRoute\Dtos\DungeonRouteSearchFilter;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Stub\StubRepository;
use Illuminate\Support\Collection;

class DungeonRouteRepository extends StubRepository implements DungeonRouteRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRoute::class);
    }

    public function generateRandomPublicKey(): string
    {
        // Just do something that is mostly unique
        return md5(uniqid());
    }
    /**
     * @param  Collection<int, DungeonRoute>|null $dungeonRoutes
     * @return Collection<int, DungeonRoute>
     */
    public function getDungeonRoutesWithExpiredThumbnails(?Collection $dungeonRoutes = null): Collection
    {
        return $dungeonRoutes ?? collect();
    }

    /**
     * @return Collection<string, Collection<int, WeeklyRoute>>
     */
    public function getWeeklyRoutes(?Dungeon $dungeon = null, ?Season $season = null): Collection
    {
        return collect();
    }

    /**
     * @return Collection<int, DungeonRoute>
     */
    public function getRoutesForUserAndDungeon(User $user, Dungeon $dungeon, int $limit): Collection
    {
        return collect();
    }

    /**
     * @return Collection<int, SimilarDungeonRoute>
     */
    public function findSimilarRoutes(DungeonRoute $dungeonRoute, int $limit = 5): Collection
    {
        return collect();
    }

    /**
     * @return Collection<int, DungeonRoute>
     */
    public function findRoutes(DungeonRouteSearchFilter $filter): Collection
    {
        return collect();
    }

    public function findCombatLogRouteByPublicKey(?string $publicKey): ?DungeonRoute
    {
        return null;
    }
}
