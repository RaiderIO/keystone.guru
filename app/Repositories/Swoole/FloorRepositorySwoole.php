<?php

namespace App\Repositories\Swoole;

use App\Models\Floor\Floor;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Swoole\Interfaces\FloorRepositorySwooleInterface;

class FloorRepositorySwoole extends DatabaseRepository implements FloorRepositorySwooleInterface
{
    public function __construct()
    {
        parent::__construct(Floor::class);
    }

    public function findByUiMapId(int $uiMapId, ?int $dungeonId = null): ?Floor
    {
        // TODO: Implement findByUiMapId() method.
    }

    public function getDefaultFloorForDungeon(int $dungeonId): ?Floor
    {
        // TODO: Implement getDefaultFloorForDungeon() method.
    }
}
