<?php

namespace App\Repositories\KillZone;

use App\Models\KillZone\KillZone;
use App\Repositories\BaseRepository;

class KillZoneRepository extends BaseRepository implements KillZoneRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(KillZone::class);
    }
}
