<?php

namespace App\Service\Wowhead;

use App\Models\GameVersion\GameVersion;
use App\Models\Npc;

interface WowheadServiceInterface
{
    public function getNpcHealth(GameVersion $gameVersion, Npc $npc): ?int;

    public function downloadMissingSpellIcons(): bool;
}
