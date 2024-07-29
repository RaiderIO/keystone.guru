<?php

namespace App\Repositories\Database;

use App\Models\MapIconType;
use App\Repositories\Interfaces\MapIconTypeRepositoryInterface;

class MapIconTypeRepository extends DatabaseRepository implements MapIconTypeRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(MapIconType::class);
    }
}
