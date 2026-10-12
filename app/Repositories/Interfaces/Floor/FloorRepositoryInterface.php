<?php

namespace App\Repositories\Interfaces\Floor;

use App\Models\Floor\Floor;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Floor                  create(array<string, mixed> $attributes)
 * @method Floor|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Floor                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Floor                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                   save(Floor $model)
 * @method bool                   update(Floor $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                   delete(Floor $model)
 * @method Collection<int, Floor> all()
 * @method bool                   exists(array<string, mixed> $columns)
 */
interface FloorRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUiMapId(int $uiMapId, ?int $dungeonId = null): ?Floor;

    /**
     * Every floor of the dungeon carrying this ui_map_id, lowest index first. A dungeon can map one in-game map onto
     * several floors, e.g. a phased copy of an area the party returns to later in the run.
     *
     * @return Collection<int, Floor>
     */
    public function findAllByUiMapId(int $uiMapId, int $dungeonId): Collection;

    /**
     * The floor the party walked onto when the logged ui_map_id changed while on $currentFloor.
     *
     * Floor indices follow the order in which the party visits the floors, so the first candidate at or ahead of the
     * current floor wins. When every candidate lies behind, the party went back, and the most recently passed one
     * wins. A ui_map_id carried by a single floor resolves to that floor wherever the party is.
     */
    public function findByUiMapIdInVisitOrder(int $uiMapId, int $dungeonId, ?Floor $currentFloor): ?Floor;

    /**
     * findByUiMapIdInVisitOrder() over candidates fetched earlier with findAllByUiMapId().
     *
     * @param Collection<int, Floor> $candidateFloors
     */
    public function pickFloorInVisitOrder(Collection $candidateFloors, ?Floor $currentFloor): ?Floor;

    public function getDefaultFloorForDungeon(int $dungeonId): ?Floor;
}
