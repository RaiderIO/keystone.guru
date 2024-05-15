<?php

namespace App\Repositories\Database\Floor;

use App\Models\Floor\FloorUnionArea;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Floor\FloorUnionAreaRepositoryInterface;

class FloorUnionAreaRepository extends DatabaseRepository implements FloorUnionAreaRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(FloorUnionArea::class);
    }
}
