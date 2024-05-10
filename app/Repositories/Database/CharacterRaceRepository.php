<?php

namespace App\Repositories\Database;

use App\Models\CharacterRace;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CharacterRaceRepositoryInterface;

class CharacterRaceRepository extends DatabaseRepository implements CharacterRaceRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CharacterRace::class);
    }
}
