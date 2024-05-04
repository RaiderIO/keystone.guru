<?php

namespace App\Repositories\Database\KillZone;

use App\Models\KillZone\KillZone;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;

class KillZoneRepository extends DatabaseRepository implements KillZoneRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(KillZone::class);
    }
}
