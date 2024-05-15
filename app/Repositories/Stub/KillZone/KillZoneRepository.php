<?php

namespace App\Repositories\Stub\KillZone;

use App\Models\KillZone\KillZone;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Stub\StubRepository;

class KillZoneRepository extends StubRepository implements KillZoneRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(KillZone::class);
    }
}
