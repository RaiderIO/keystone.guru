<?php

namespace App\Repositories\Database;

use App\Models\RaidMarker;
use App\Repositories\Interfaces\RaidMarkerRepositoryInterface;

class RaidMarkerRepository extends DatabaseRepository implements RaidMarkerRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(RaidMarker::class);
    }
}
