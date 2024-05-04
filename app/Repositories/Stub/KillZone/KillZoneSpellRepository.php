<?php

namespace App\Repositories\Stub\KillZone;

use App\Models\KillZone\KillZoneSpell;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Repositories\Stub\StubRepository;

class KillZoneSpellRepository extends StubRepository implements KillZoneSpellRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(KillZoneSpell::class);
    }
}
