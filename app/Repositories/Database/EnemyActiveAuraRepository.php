<?php

namespace App\Repositories\Database;

use App\Models\EnemyActiveAura;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\EnemyActiveAuraRepositoryInterface;

class EnemyActiveAuraRepository extends DatabaseRepository implements EnemyActiveAuraRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(EnemyActiveAura::class);
    }
}
