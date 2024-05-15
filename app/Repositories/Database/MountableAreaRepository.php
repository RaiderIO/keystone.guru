<?php

namespace App\Repositories\Database;

use App\Models\MountableArea;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\MountableAreaRepositoryInterface;

class MountableAreaRepository extends DatabaseRepository implements MountableAreaRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(MountableArea::class);
    }
}
