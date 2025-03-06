<?php

namespace App\Repositories\Database\Npc;

use App\Models\Npc\NpcClass;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\NpcClassRepositoryInterface;

class NpcClassRepository extends DatabaseRepository implements NpcClassRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcClass::class);
    }
}
