<?php

namespace App\Repositories\Database\Npc;

use App\Models\Npc\NpcSpell;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\NpcSpellRepositoryInterface;

class NpcSpellRepository extends DatabaseRepository implements NpcSpellRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcSpell::class);
    }
}
