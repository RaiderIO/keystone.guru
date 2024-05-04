<?php

namespace App\Repositories\Database\KillZone;

use App\Models\KillZone\KillZoneSpell;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;

class KillZoneSpellRepository extends DatabaseRepository implements KillZoneSpellRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(KillZoneSpell::class);
    }
}
