<?php

namespace App\Repositories\Database\Floor;

use App\Models\Floor\FloorUnion;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Floor\FloorUnionRepositoryInterface;

class FloorUnionRepository extends DatabaseRepository implements FloorUnionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(FloorUnion::class);
    }
}
