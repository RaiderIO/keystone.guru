<?php

namespace App\Repositories\KillZone;

use App\Models\KillZone\KillZoneEnemy;
use App\Repositories\BaseRepository;

class KillZoneEnemyRepository extends BaseRepository implements KillZoneEnemyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(KillZoneEnemy::class);
    }
}
