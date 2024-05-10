<?php

namespace App\Repositories\Database\Enemies;

use App\Models\Enemies\PridefulEnemy;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Enemies\PridefulEnemyRepositoryInterface;

class PridefulEnemyRepository extends DatabaseRepository implements PridefulEnemyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(PridefulEnemy::class);
    }
}
