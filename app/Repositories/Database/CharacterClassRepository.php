<?php

namespace App\Repositories\Database;

use App\Models\CharacterClass;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CharacterClassRepositoryInterface;

class CharacterClassRepository extends DatabaseRepository implements CharacterClassRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CharacterClass::class);
    }
}
