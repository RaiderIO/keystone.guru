<?php

namespace App\Repositories\Swoole;

use App\Models\Dungeon;
use App\Repositories\Database\Npc\NpcRepository;
use App\Repositories\Swoole\Interfaces\NpcRepositorySwooleInterface;
use App\Repositories\Swoole\Traits\ClonesCollections;
use Illuminate\Support\Collection;

class NpcRepositorySwoole extends NpcRepository implements NpcRepositorySwooleInterface
{
    use ClonesCollections;

    private Collection $inUseNpcsByDungeonId;
    private Collection $inUseNpcIdsByDungeonId;

    public function __construct()
    {
        parent::__construct();

        $this->inUseNpcsByDungeonId   = collect();
        $this->inUseNpcIdsByDungeonId = collect();
    }

    /**
     * @inheritDoc
     */
    public function getInUseNpcs(Dungeon $dungeon): Collection
    {
        if (!$this->inUseNpcsByDungeonId->has($dungeon->id)) {
            $inUseNpcs = parent::getInUseNpcs($dungeon);

            $this->inUseNpcsByDungeonId->put($dungeon->id, $inUseNpcs);
        }

        return $this->cloneCollection(
            $this->inUseNpcsByDungeonId->get($dungeon->id)
        );
    }

    /**
     * @inheritDoc
     */
    public function getInUseNpcIds(Dungeon $dungeon, ?Collection $inUseNpcs = null): Collection
    {
        if (!$this->inUseNpcIdsByDungeonId->has($dungeon->id)) {

            $inUseNpcIds = parent::getInUseNpcIds($dungeon, $inUseNpcs);

            $this->inUseNpcIdsByDungeonId->put($dungeon->id, $inUseNpcIds);
        }

        return $this->copyCollection(
            $this->inUseNpcIdsByDungeonId->get($dungeon->id)
        );
    }
}
