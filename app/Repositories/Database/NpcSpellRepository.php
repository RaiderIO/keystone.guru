<?php

namespace App\Repositories\Database;

use App\Models\NpcSpell;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\NpcSpellRepositoryInterface;

class NpcSpellRepository extends DatabaseRepository implements NpcSpellRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcSpell::class);
    }
}
