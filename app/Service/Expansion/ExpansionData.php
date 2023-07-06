<?php

namespace App\Service\Expansion;

use App\Models\Expansion;
use Illuminate\Support\Collection;

class ExpansionData
{
    /** @var Expansion */
    private Expansion $expansion;

    /** @var Collection */
    private Collection $activeDungeons;

    /** @var ExpansionSeason */
    private ExpansionSeason $expansionSeason;

    /**
     * @param ExpansionServiceInterface $expansionService
     * @param Expansion $expansion
     */
    public function __construct(ExpansionServiceInterface $expansionService, Expansion $expansion)
    {
        $this->expansion       = $expansion;
        $this->activeDungeons  = $expansion->dungeons;
        $this->expansionSeason = new ExpansionSeason($expansionService, $expansion);
    }

    /**
     * @return Expansion
     */
    public function getExpansion(): Expansion
    {
        return $this->expansion;
    }

    /**
     * @return Collection
     */
    public function getActiveDungeons(): Collection
    {
        return $this->activeDungeons;
    }

    /**
     * @return ExpansionSeason
     */
    public function getExpansionSeason(): ExpansionSeason
    {
        return $this->expansionSeason;
    }
}
