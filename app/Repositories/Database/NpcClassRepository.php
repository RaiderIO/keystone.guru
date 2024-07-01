<?php

namespace App\Repositories\Database;

use App\Models\NpcClass;
use App\Repositories\Interfaces\NpcClassRepositoryInterface;

class NpcClassRepository extends DatabaseRepository implements NpcClassRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcClass::class);
    }
}
