<?php

namespace App\Service\CombatLog\Filters;

use App\Logic\CombatLog\BaseEvent;
use App\Models\DungeonRoute;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use Illuminate\Support\Collection;

class CombatLogDungeonRouteFilter implements CombatLogParserInterface
{
    /** @var Collection|BaseResultEvent[] */
    private Collection $resultEvents;

    private SpecialEventsFilter $specialEventsFilter;

    private CombatFilter $combatFilter;

    public function __construct()
    {
        $this->resultEvents        = collect();
        $this->specialEventsFilter = new SpecialEventsFilter($this->resultEvents);
        $this->combatFilter        = new CombatFilter($this->resultEvents);
    }

    /**
     * @param DungeonRoute $dungeonRoute
     *
     * @return void
     */
    public function setDungeonRoute(DungeonRoute $dungeonRoute): self
    {
        $this->combatFilter->setValidNpcIds($dungeonRoute->dungeon->getNpcIdsWithEnemyForces());
        
        return $this;
    }

    /**
     * @param BaseEvent $combatLogEvent
     * @param int       $lineNr
     *
     * @return bool
     */
    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool
    {
        $specialEventsFilterResult = $this->specialEventsFilter->parse($combatLogEvent, $lineNr);

        $combatFilterResult = $this->combatFilter->parse($combatLogEvent, $lineNr);

        return $specialEventsFilterResult || $combatFilterResult;
    }

    /**
     * @return Collection|BaseResultEvent[]
     */
    public function getResultEvents(): Collection
    {
        return $this->resultEvents->sortBy(function (BaseResultEvent $baseResultEvent)
        {
            return $baseResultEvent->getBaseEvent()->getTimestamp()->getTimestampMs();
        });
    }
}
