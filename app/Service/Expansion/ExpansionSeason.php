<?php

namespace App\Service\Expansion;

use App\Models\Affix;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;

class ExpansionSeason
{
    /** @var Season|null */
    private ?Season $season = null;

    /** @var ExpansionSeasonAffixGroups */
    private ExpansionSeasonAffixGroups $affixGroups;

    /** @var bool */
    private bool $isAwakened = false;

    /** @var bool */
    private bool $isPrideful = false;

    /** @var bool */
    private bool $isTormented = false;

    /** @var bool */
    private bool $isInfernal = false;

    public function __construct(ExpansionServiceInterface $expansionService, Expansion $expansion, GameServerRegion $gameServerRegion)
    {
        $this->season = $expansionService->getCurrentSeason($expansion, $gameServerRegion);

        if ($this->season !== null) {
            $this->isAwakened  = $this->season->seasonal_affix_id === Affix::ALL[Affix::AFFIX_AWAKENED];
            $this->isPrideful  = $this->season->seasonal_affix_id === Affix::ALL[Affix::AFFIX_PRIDEFUL];
            $this->isTormented = $this->season->seasonal_affix_id === Affix::ALL[Affix::AFFIX_TORMENTED];
            $this->isInfernal  = $this->season->seasonal_affix_id === Affix::ALL[Affix::AFFIX_INFERNAL];
        }

        $this->affixGroups = new ExpansionSeasonAffixGroups($expansionService, $expansion, $gameServerRegion, $this);
    }

    /**
     * @return Season|null
     */
    public function getSeason(): ?Season
    {
        return $this->season;
    }

    /**
     * @return ExpansionSeasonAffixGroups
     */
    public function getAffixGroups(): ExpansionSeasonAffixGroups
    {
        return $this->affixGroups;
    }

    /**
     * @return bool
     */
    public function isAwakened(): bool
    {
        return $this->isAwakened;
    }

    /**
     * @return bool
     */
    public function isPrideful(): bool
    {
        return $this->isPrideful;
    }

    /**
     * @return bool
     */
    public function isTormented(): bool
    {
        return $this->isTormented;
    }

    /**
     * @return bool
     */
    public function isInfernal(): bool
    {
        return $this->isInfernal;
    }
}
