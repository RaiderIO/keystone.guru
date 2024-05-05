<?php

namespace App\Repositories\Database;

use App\Models\NpcType;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\NpcTypeRepositoryInterface;

class NpcTypeRepository extends DatabaseRepository implements NpcTypeRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcType::class);
    }
}
