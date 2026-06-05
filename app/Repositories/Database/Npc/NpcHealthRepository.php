<?php

namespace App\Repositories\Database\Npc;

use App\Models\Npc\NpcHealth;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\NpcHealthRepositoryInterface;

class NpcHealthRepository extends DatabaseRepository implements NpcHealthRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcHealth::class);
    }
}
