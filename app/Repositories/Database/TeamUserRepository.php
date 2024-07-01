<?php

namespace App\Repositories\Database;

use App\Models\TeamUser;
use App\Repositories\Interfaces\TeamUserRepositoryInterface;

class TeamUserRepository extends DatabaseRepository implements TeamUserRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(TeamUser::class);
    }
}
