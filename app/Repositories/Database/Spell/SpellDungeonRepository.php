<?php

namespace App\Repositories\Database\Spell;

use App\Models\Spell\SpellDungeon;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Spell\SpellDungeonRepositoryInterface;

class SpellDungeonRepository extends DatabaseRepository implements SpellDungeonRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(SpellDungeon::class);
    }
}
