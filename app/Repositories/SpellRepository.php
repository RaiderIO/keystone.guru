<?php

namespace App\Repositories;

use App\Models\Spell;

class SpellRepository extends BaseRepository implements SpellRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Spell::class);
    }
}
