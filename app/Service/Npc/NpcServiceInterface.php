<?php

namespace App\Service\Npc;

use App\Models\Dungeon;
use Illuminate\Support\Collection;

interface NpcServiceInterface
{
    public function getNpcsForDropdown(Dungeon $dungeon, bool $includeAllDungeonsNpcs = false): Collection;
}
