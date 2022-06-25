<?php

namespace App\Service\Expansion;

use App\Models\Affix;
use App\Models\Expansion;
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

    /**
     * @param ExpansionServiceInterface $expansionService
     * @param Expansion $expansion
     */
    public function __construct(ExpansionServiceInterface $expansionService, Expansion $expansion)
    {
        $this->season = $expansionService->getCurrentSeason($expansion);

        if ($this->season !== null) {
            $this->isAwakened  = $this->season->seasonal_affix_id === Affix::where('key', Affix::AFFIX_AWAKENED)->first()->id;
            $this->isPrideful  = $this->season->seasonal_affix_id === Affix::where('key', Affix::AFFIX_PRIDEFUL)->first()->id;
            $this->isTormented = $this->season->seasonal_affix_id === Affix::where('key', Affix::AFFIX_TORMENTED)->first()->id;
            $this->isInfernal  = $this->season->seasonal_affix_id === Affix::where('key', Affix::AFFIX_INFERNAL)->first()->id;

        }

        $this->affixGroups = new ExpansionSeasonAffixGroups($expansionService, $expansion, $this);
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
