<?php

namespace App\Repositories\Database\Floor;

use App\Models\Floor\FloorCoupling;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Floor\FloorCouplingRepositoryInterface;

class FloorCouplingRepository extends DatabaseRepository implements FloorCouplingRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(FloorCoupling::class);
    }
}
