<?php

namespace App\Repositories\Swoole;

use App\Models\Floor\Floor;
use App\Repositories\Database\Floor\FloorRepository;
use App\Repositories\Swoole\Interfaces\FloorRepositorySwooleInterface;
use Illuminate\Support\Collection;

class FloorRepositorySwoole extends FloorRepository implements FloorRepositorySwooleInterface
{
    /** @var Collection<string, Floor> */
    private readonly Collection $floorsByUiMapIdAndDungeonId;

    /** @var Collection<int, Floor> */
    private Collection $defaultFloorByDungeonId;

    public function __construct()
    {
        parent::__construct();

        $this->floorsByUiMapIdAndDungeonId = collect();
        $this->defaultFloorByDungeonId     = collect();
    }

    #[\Override]
    public function findByUiMapId(int $uiMapId, ?int $dungeonId = null): ?Floor
    {
        if ($uiMapId === 0) {
            return null;
        }

        $key = sprintf('%d-%s', $uiMapId, $dungeonId ?? 'null');

        if (!$this->floorsByUiMapIdAndDungeonId->has($key)) {
            $this->floorsByUiMapIdAndDungeonId->put($key, parent::findByUiMapId($uiMapId, $dungeonId));
        }

        return $this->floorsByUiMapIdAndDungeonId->get($key);
    }

    #[\Override]
    public function getDefaultFloorForDungeon(int $dungeonId): ?Floor
    {
        if ($this->defaultFloorByDungeonId->has($dungeonId)) {
            return clone $this->defaultFloorByDungeonId->get($dungeonId);
        } // If we DID have entries - we just didn't have the one we were looking for, return null
        elseif ($this->defaultFloorByDungeonId->isNotEmpty()) {
            return null;
        }

        // Get it all at once and store it in the cache
        $this->defaultFloorByDungeonId = Floor::where('default', 1)->get()->keyBy('dungeon_id');

        // Hotfix for Seat of the Triumvirate not having default floor set
        if ($dungeonId === 12) {
            $this->defaultFloorByDungeonId->put(12, Floor::find(37));
        }

        return clone $this->defaultFloorByDungeonId->get($dungeonId);
    }
}
