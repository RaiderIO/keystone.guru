<?php

namespace App\Repositories\Database;

use App\Models\EnemyPack;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\EnemyPackRepositoryInterface;

class EnemyPackRepository extends DatabaseRepository implements EnemyPackRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(EnemyPack::class);
    }
}
