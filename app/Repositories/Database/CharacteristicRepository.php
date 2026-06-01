<?php

namespace App\Repositories\Database;

use App\Models\Characteristic;
use App\Repositories\Interfaces\CharacteristicRepositoryInterface;

class CharacteristicRepository extends DatabaseRepository implements CharacteristicRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Characteristic::class);
    }
}
