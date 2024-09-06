<?php

namespace App\Repositories\Database;

use App\Models\Team;
use App\Repositories\Interfaces\TeamRepositoryInterface;

class TeamRepository extends DatabaseRepository implements TeamRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Team::class);
    }
}
