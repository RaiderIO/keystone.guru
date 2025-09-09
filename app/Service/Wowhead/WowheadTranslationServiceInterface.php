<?php

namespace App\Service\Wowhead;

use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Collection;

interface WowheadTranslationServiceInterface
{

    /** @return Collection<string, Collection<string, string>> */
    public function getNpcNames(GameVersion $gameVersion): Collection;

    /** @return Collection<string, Collection<string, string>> */
    public function getSpellNames(GameVersion $gameVersion): Collection;

    public function getDungeonNames(): Collection;

    public function getFloorNames(): Collection;
}
