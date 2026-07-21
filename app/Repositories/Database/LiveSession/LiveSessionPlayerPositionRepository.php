<?php

namespace App\Repositories\Database\LiveSession;

use App\Models\LiveSession\LiveSessionPlayerPosition;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\LiveSession\LiveSessionPlayerPositionRepositoryInterface;

class LiveSessionPlayerPositionRepository extends DatabaseRepository implements LiveSessionPlayerPositionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(LiveSessionPlayerPosition::class);
    }
}
