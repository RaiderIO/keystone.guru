<?php

namespace App\Repositories\Database;

use App\Models\SeasonDungeon;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\SeasonDungeonRepositoryInterface;

class SeasonDungeonRepository extends DatabaseRepository implements SeasonDungeonRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(SeasonDungeon::class);
    }
}
