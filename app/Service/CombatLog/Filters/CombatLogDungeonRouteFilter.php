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

    /** @var Collection|CombatLogParserInterface[] */
    private Collection $filters;

    private SpecialEventsFilter $specialEventsFilter;

    private CombatFilter $combatFilter;

    private SpellFilter $spellFilter;

    public function __construct()
    {
        $this->resultEvents        = collect();
        $this->specialEventsFilter = new SpecialEventsFilter($this->resultEvents);
        $this->combatFilter        = new CombatFilter($this->resultEvents);
        $this->spellFilter         = new SpellFilter($this->resultEvents);

        $this->filters = collect([
            $this->specialEventsFilter,
            $this->combatFilter,
            $this->spellFilter,
        ]);
    }

    /**
     * @param DungeonRoute $dungeonRoute
     *
     * @return void
     */
    public function setDungeonRoute(DungeonRoute $dungeonRoute): self
    {
        $this->combatFilter->setValidNpcIds($dungeonRoute->dungeon->getInUseNpcIds());

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
        $result = false;

        foreach ($this->filters as $filter) {
            $result = $filter->parse($combatLogEvent, $lineNr) || $result;
        }

        return $result;
    }

    /**
     * @return Collection|BaseResultEvent[]
     */
    public function getResultEvents(): Collection
    {
        return $this->resultEvents->sortBy(function (BaseResultEvent $baseResultEvent) {
            return $baseResultEvent->getBaseEvent()->getTimestamp()->getTimestampMs();
        });
    }
}
