<?php

namespace App\Repositories\Database;

use App\Models\Faction;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\FactionRepositoryInterface;

class FactionRepository extends DatabaseRepository implements FactionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Faction::class);
    }
}
