<?php

namespace App\Repositories\Swoole;

use App\Models\Floor\Floor;
use App\Repositories\Database\Floor\FloorRepository;
use App\Repositories\Swoole\Interfaces\FloorRepositorySwooleInterface;
use Illuminate\Support\Collection;

class FloorRepositorySwoole extends FloorRepository implements FloorRepositorySwooleInterface
{
    private Collection $floorsByUiMapIdAndDungeonId;
    private Collection $defaultFloorByDungeonId;

    public function __construct()
    {
        parent::__construct();

        $this->floorsByUiMapIdAndDungeonId = collect();
        $this->defaultFloorByDungeonId     = collect();
    }

    public function findByUiMapId(int $uiMapId, ?int $dungeonId = null): ?Floor
    {
        $key = sprintf('%d-%s', $uiMapId, $dungeonId ?? 'null');

        if ($this->floorsByUiMapIdAndDungeonId->has($key)) {
            return $this->floorsByUiMapIdAndDungeonId->get($key);
        }

        $floor = parent::findByUiMapId($uiMapId, $dungeonId);

        $this->floorsByUiMapIdAndDungeonId->put($key, $floor);

        return $floor;
    }

    public function getDefaultFloorForDungeon(int $dungeonId): ?Floor
    {
        if ($this->defaultFloorByDungeonId->has($dungeonId)) {
            return clone $this->defaultFloorByDungeonId->get($dungeonId);
        } // If we DID have entries - we just didn't have the one we were looking for, return null
        else if ($this->defaultFloorByDungeonId->isNotEmpty()) {
            return null;
        }

        // Get it all at once and store it in the cache
        $this->defaultFloorByDungeonId = Floor::where('default', 1)->get()->keyBy('dungeon_id');

        return clone $this->defaultFloorByDungeonId->get($dungeonId);
    }
}
