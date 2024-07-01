<?php

namespace App\Repositories\Database;

use App\Models\Polyline;
use App\Repositories\Interfaces\PolylineRepositoryInterface;

class PolylineRepository extends DatabaseRepository implements PolylineRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Polyline::class);
    }
}
