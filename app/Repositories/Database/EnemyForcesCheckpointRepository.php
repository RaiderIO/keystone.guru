<?php

namespace App\Repositories\Database;

use App\Models\EnemyForcesCheckpoint;
use App\Repositories\Interfaces\EnemyForcesCheckpointRepositoryInterface;

class EnemyForcesCheckpointRepository extends DatabaseRepository implements EnemyForcesCheckpointRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(EnemyForcesCheckpoint::class);
    }
}
