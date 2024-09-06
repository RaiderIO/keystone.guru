<?php

namespace App\Repositories\Database;

use App\Models\CharacterRaceClassCoupling;
use App\Repositories\Interfaces\CharacterRaceClassCouplingRepositoryInterface;

class CharacterRaceClassCouplingRepository extends DatabaseRepository implements CharacterRaceClassCouplingRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CharacterRaceClassCoupling::class);
    }
}
