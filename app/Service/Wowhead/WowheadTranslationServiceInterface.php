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

    /** @return Collection<string, mixed> */
    public function getDungeonNames(): Collection;

    /** @return Collection<string, mixed> */
    public function getFloorNames(): Collection;

    /**
     * Every zone Wowhead lists for the game version, not only instances.
     *
     * @return Collection<string, array<int, string>> Zone names by zone ID, per locale.
     */
    public function getZoneNames(GameVersion $gameVersion): Collection;

    /**
     * @return Collection<string, array<string, string>> Continent names by Wowhead continent key (e.g. POSTMASTER_PIPE_KALIMDOR), per locale.
     */
    public function getContinentNames(): Collection;
}
