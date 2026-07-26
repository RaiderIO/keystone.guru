<?php

namespace App\Repositories\Database;

use App\Models\EnemyForcesRegion;
use App\Repositories\Interfaces\EnemyForcesRegionRepositoryInterface;

class EnemyForcesRegionRepository extends DatabaseRepository implements EnemyForcesRegionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(EnemyForcesRegion::class);
    }
}
