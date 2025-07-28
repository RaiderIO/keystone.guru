<?php

namespace App\Service\Wowhead;

use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Service\Wowhead\Dtos\LocalizedNpcName;
use App\Service\Wowhead\Dtos\SpellDataResult;
use Illuminate\Support\Collection;

interface WowheadServiceInterface
{
    public function getNpcHealth(GameVersion $gameVersion, Npc $npc): ?int;

    public function downloadMissingSpellIcons(): bool;

    public function getNpcDisplayId(GameVersion $gameVersion, Npc $npc): ?int;

    public function getSpellData(GameVersion $gameVersion, int $spellId): ?SpellDataResult;

    /** @return Collection<string, Collection<string, string>> */
    public function getNpcNames(GameVersion $gameVersion): Collection;

    /** @return Collection<string, Collection<string, string>> */
    public function getSpellNames(GameVersion $gameVersion): Collection;
}
