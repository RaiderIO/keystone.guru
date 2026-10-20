<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\GameVersion\GameVersion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteCollection                  create(array<string, mixed> $attributes)
 * @method DungeonRouteCollection|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteCollection                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteCollection                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                    save(DungeonRouteCollection $model)
 * @method bool                                    update(DungeonRouteCollection $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                    delete(DungeonRouteCollection $model)
 * @method Collection<int, DungeonRouteCollection> all()
 * @method bool                                    exists(array<int, string> $columns)
 */
interface DungeonRouteCollectionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Takes a row lock on the collection until the surrounding transaction ends, serialising concurrent changes to it.
     */
    public function lockForUpdate(DungeonRouteCollection $dungeonRouteCollection): void;

    /**
     * Stamps the collection as changed, for changes that only touch its routes.
     */
    public function touchUpdatedAt(DungeonRouteCollection $dungeonRouteCollection): void;

    /**
     * The world published collections of a game version that hold at least one route anyone may open, most recently
     * changed first, with everything the collection tile reads eager loaded.
     *
     * @param  string|null                                       $search  Matched against the collection's name.
     * @param  Dungeon|null                                      $dungeon Only collections holding an openable route for this dungeon.
     * @return LengthAwarePaginator<int, DungeonRouteCollection>
     */
    public function paginateWorldPublished(
        GameVersion                     $gameVersion,
        ?string                         $search,
        ?DungeonRouteCollectionCategory $dungeonRouteCollectionCategory,
        ?Dungeon                        $dungeon,
        int                             $perPage,
    ): LengthAwarePaginator;
}
