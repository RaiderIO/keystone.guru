<?php

namespace App\Repositories\Database;

use App\Models\CharacterClassSpecialization;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CharacterClassSpecializationRepositoryInterface;

class CharacterClassSpecializationRepository extends DatabaseRepository implements CharacterClassSpecializationRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CharacterClassSpecialization::class);
    }
}
