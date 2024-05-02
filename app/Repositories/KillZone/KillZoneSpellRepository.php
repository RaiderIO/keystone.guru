<?php

namespace App\Repositories\KillZone;

use App\Models\KillZone\KillZoneSpell;
use App\Repositories\BaseRepository;

class KillZoneSpellRepository extends BaseRepository implements KillZoneSpellRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(KillZoneSpell::class);
    }
}
