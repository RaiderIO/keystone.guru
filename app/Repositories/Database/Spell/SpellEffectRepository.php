<?php

namespace App\Repositories\Database\Spell;

use App\Models\Spell\SpellEffect;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Spell\SpellEffectRepositoryInterface;

class SpellEffectRepository extends DatabaseRepository implements SpellEffectRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(SpellEffect::class);
    }
}
