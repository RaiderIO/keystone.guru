<?php

namespace App\Repositories\Database\Npc;

use App\Models\Npc\NpcType;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\NpcTypeRepositoryInterface;

class NpcTypeRepository extends DatabaseRepository implements NpcTypeRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcType::class);
    }
}
