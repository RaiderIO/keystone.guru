<?php

namespace App\Repositories\Database\Floor;

use App\Models\Floor\Floor;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FloorRepository extends DatabaseRepository implements FloorRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Floor::class);
    }

    public function findByUiMapId(int $uiMapId, ?int $dungeonId = null): ?Floor
    {
        if ($uiMapId === 0) {
            return null;
        }

        return Floor::where('ui_map_id', Floor::UI_MAP_ID_MAPPING[$uiMapId] ?? $uiMapId)
            ->when($dungeonId !== null, static fn(Builder $builder) => $builder->where('dungeon_id', $dungeonId))
            ->first();
    }

    public function findAllByUiMapId(int $uiMapId, int $dungeonId): Collection
    {
        if ($uiMapId === 0) {
            return collect();
        }

        return Floor::where('ui_map_id', Floor::UI_MAP_ID_MAPPING[$uiMapId] ?? $uiMapId)
            ->where('dungeon_id', $dungeonId)
            ->orderBy('index')
            ->get()
            ->toBase();
    }

    public function findByUiMapIdInVisitOrder(int $uiMapId, int $dungeonId, ?Floor $currentFloor): ?Floor
    {
        $candidateFloors = $this->findAllByUiMapId($uiMapId, $dungeonId);

        if ($currentFloor === null) {
            return $candidateFloors->first();
        }

        return $candidateFloors->first(static fn(Floor $floor) => $floor->index >= $currentFloor->index)
            ?? $candidateFloors->last();
    }

    public function getDefaultFloorForDungeon(int $dungeonId): ?Floor
    {
        // Hotfix for Seat of the Triumvirate not having default floor set
        if ($dungeonId === 12) {
            return Floor::find(37);
        }

        return Floor::where('dungeon_id', $dungeonId)->where('default', 1)->first();
    }
}
