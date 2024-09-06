<?php

namespace App\Repositories\Database\KillZone;

use App\Models\KillZone\KillZoneEnemy;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;

class KillZoneEnemyRepository extends DatabaseRepository implements KillZoneEnemyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(KillZoneEnemy::class);
    }
}
