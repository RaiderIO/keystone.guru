<?php

namespace App\Service\CombatLog\Filters\MappingVersion;

use App\Service\CombatLog\Filters\BaseCombatLogFilter;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;

class CombatLogDungeonOrRaidFilter extends BaseCombatLogFilter implements CombatLogParserInterface
{
    private readonly SpecialEventsFilter $specialEventsFilter;

    private readonly CombatFilter $combatFilter;

    public function __construct()
    {
        parent::__construct();

        $this->specialEventsFilter = new SpecialEventsFilter($this->resultEvents);
        $this->combatFilter = new CombatFilter($this->resultEvents);

        $this->addFilter($this->specialEventsFilter);
        $this->addFilter($this->combatFilter);
    }
}
