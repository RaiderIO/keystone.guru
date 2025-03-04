<?php

namespace App\Repositories\Swoole;

use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\NpcRepositoryInterface;
use App\Repositories\Swoole\Interfaces\NpcRepositorySwooleInterface;
use Illuminate\Support\Collection;

class NpcRepositorySwoole extends DatabaseRepository implements NpcRepositorySwooleInterface
{
    public function __construct()
    {
        parent::__construct(Npc::class);
    }

    /**
     * @inheritDoc
     */
    public function getInUseNpcs(Dungeon $dungeon): Collection
    {
        // TODO: Implement getInUseNpcs() method.
    }

    /**
     * @inheritDoc
     */
    public function getInUseNpcIds(Dungeon $dungeon, ?Collection $inUseNpcs = null): Collection
    {
        // TODO: Implement getInUseNpcIds() method.
    }
}
