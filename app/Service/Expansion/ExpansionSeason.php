<?php

namespace App\Service\Expansion;

use App\Models\Affix;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;

class ExpansionSeason
{
    private ?Season $season = null;

    private readonly ExpansionSeasonAffixGroups $affixGroups;

    private bool $isAwakened = false;

    private bool $isPrideful = false;

    private bool $isTormented = false;

    private bool $isInfernal = false;

    public function __construct(ExpansionServiceInterface $expansionService, Expansion $expansion, GameServerRegion $gameServerRegion)
    {
        $this->season = $expansionService->getCurrentSeason($expansion, $gameServerRegion);

        if ($this->season !== null) {
            $this->isAwakened = $this->season->seasonal_affix_id === Affix::ALL[Affix::AFFIX_AWAKENED];
            $this->isPrideful = $this->season->seasonal_affix_id === Affix::ALL[Affix::AFFIX_PRIDEFUL];
            $this->isTormented = $this->season->seasonal_affix_id === Affix::ALL[Affix::AFFIX_TORMENTED];
            $this->isInfernal = $this->season->seasonal_affix_id === Affix::ALL[Affix::AFFIX_INFERNAL];
        }

        $this->affixGroups = new ExpansionSeasonAffixGroups($expansionService, $expansion, $gameServerRegion, $this);
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function getAffixGroups(): ExpansionSeasonAffixGroups
    {
        return $this->affixGroups;
    }

    public function isAwakened(): bool
    {
        return $this->isAwakened;
    }

    public function isPrideful(): bool
    {
        return $this->isPrideful;
    }

    public function isTormented(): bool
    {
        return $this->isTormented;
    }

    public function isInfernal(): bool
    {
        return $this->isInfernal;
    }
}
