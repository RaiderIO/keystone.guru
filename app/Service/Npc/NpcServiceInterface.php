<?php

namespace App\Service\Npc;

use Illuminate\Support\Collection;

interface NpcServiceInterface
{
    public function getNpcsForDropdown(Collection $dungeons): Collection;
}
