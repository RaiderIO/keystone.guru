<?php

namespace App\Service\CombatLog\Filters\DungeonRoute;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Service\CombatLog\Filters\BaseCombatFilter;
use App\Service\CombatLog\Filters\BaseCombatLogFilter;
use Illuminate\Support\Collection;

/**
 * Collects all information required to create a dungeon route.
 */
class CombatLogDungeonRouteFilter extends BaseCombatLogFilter
{
    private readonly SpecialEventsFilter $specialEventsFilter;

    private readonly CombatFilter $combatFilter;

    private readonly SpellFilter $spellFilter;

    public function __construct()
    {
        parent::__construct();

        $this->specialEventsFilter = new SpecialEventsFilter($this->resultEvents);
        $this->combatFilter        = new CombatFilter($this->resultEvents);
        $this->spellFilter         = new SpellFilter($this->resultEvents);

        $this->addFilter($this->specialEventsFilter);
        $this->addFilter($this->combatFilter);
        $this->addFilter($this->spellFilter);
    }

    /**
     * @param Collection<int, int> $validNpcIds
     */
    public function setValidNpcIds(Collection $validNpcIds): self
    {
        $this->combatFilter->setValidNpcIds($validNpcIds);

        return $this;
    }

    /**
     * @return Collection<string, AdvancedCombatLogEvent>
     *
     * @see BaseCombatFilter::getEngagedEnemies()
     */
    public function getEngagedEnemies(): Collection
    {
        return $this->combatFilter->getEngagedEnemies();
    }
}
