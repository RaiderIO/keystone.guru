<?php

namespace App\Service\CombatLog\Filters\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\CombatLog\Filters\BaseCombatLogFilter;

class CombatLogDungeonRouteFilter extends BaseCombatLogFilter
{
    private readonly SpecialEventsFilter $specialEventsFilter;

    private readonly CombatFilter $combatFilter;

    private readonly SpellFilter $spellFilter;

    public function __construct()
    {
        parent::__construct();

        $this->specialEventsFilter = new SpecialEventsFilter($this->resultEvents);
        $this->combatFilter = new CombatFilter($this->resultEvents);
        $this->spellFilter = new SpellFilter($this->resultEvents);

        $this->addFilter($this->specialEventsFilter);
        $this->addFilter($this->combatFilter);
        $this->addFilter($this->spellFilter);
    }

    public function setDungeonRoute(DungeonRoute $dungeonRoute): self
    {
        $this->combatFilter->setValidNpcIds($dungeonRoute->dungeon->getInUseNpcIds());

        return $this;
    }
}
