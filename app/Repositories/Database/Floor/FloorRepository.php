<?php

namespace App\Repositories\Database\Floor;

use App\Models\Floor\Floor;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

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

    public function getDefaultFloorForDungeon(int $dungeonId): ?Floor
    {
        return Floor::where('dungeon_id', $dungeonId)->where('default', 1)->first();
    }
}
