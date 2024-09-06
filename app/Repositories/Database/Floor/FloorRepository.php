<?php

namespace App\Repositories\Database\Floor;

use App\Models\Floor\Floor;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;

class FloorRepository extends DatabaseRepository implements FloorRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Floor::class);
    }
}
