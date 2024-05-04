<?php

namespace App\Repositories\Database;

use App\Models\Spell;
use App\Repositories\Interfaces\SpellRepositoryInterface;

class SpellRepository extends DatabaseRepository implements SpellRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Spell::class);
    }
}
