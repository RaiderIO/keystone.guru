<?php

namespace App\Repositories\Database\Laratrust;

use App\Models\Laratrust\Team;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Laratrust\TeamRepositoryInterface;

class TeamRepository extends DatabaseRepository implements TeamRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Team::class);
    }
}
