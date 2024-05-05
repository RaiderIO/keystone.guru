<?php

namespace App\Repositories\Database;

use App\Models\MapIcon;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\MapIconRepositoryInterface;

class MapIconRepository extends DatabaseRepository implements MapIconRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(MapIcon::class);
    }
}
