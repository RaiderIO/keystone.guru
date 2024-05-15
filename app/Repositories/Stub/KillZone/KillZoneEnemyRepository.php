<?php

namespace App\Repositories\Stub\KillZone;

use App\Models\KillZone\KillZoneEnemy;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Stub\StubRepository;

class KillZoneEnemyRepository extends StubRepository implements KillZoneEnemyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(KillZoneEnemy::class);
    }
}
