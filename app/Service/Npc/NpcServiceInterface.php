<?php

namespace App\Service\Npc;

use Illuminate\Support\Collection;

interface NpcServiceInterface
{
    /**
     * @param  Collection<int, \App\Models\Dungeon> $dungeons
     * @return Collection<int, mixed>
     */
    public function getNpcsForDropdown(Collection $dungeons): Collection;
}
