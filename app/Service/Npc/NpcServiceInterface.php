<?php

namespace App\Service\Npc;

use App\Models\Dungeon;
use Illuminate\Support\Collection;

interface NpcServiceInterface
{
    /**
     * @param Dungeon $dungeon
     * @param bool $includeAllDungeonsNpcs
     * @return Collection
     */
    public function getNpcsForDropdown(Dungeon $dungeon, bool $includeAllDungeonsNpcs = false): Collection;
}
