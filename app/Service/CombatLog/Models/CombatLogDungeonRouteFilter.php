<?php

namespace App\Service\CombatLog\Models;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\SpecialEvents\MapChange;
use App\Models\DungeonRoute;
use App\Service\CombatLog\Models\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\Models\ResultEvents\MapChange as MapChangeResultEvent;
use Illuminate\Support\Collection;

class CombatLogDungeonRouteFilter
{

    private DungeonRoute $dungeonRoute;

    /** @var Collection|BaseEvent[] */
    private Collection $combatLogEvents;

//    private DungeonRouteBuilderLoggingInterface $log;

    /**
     * @param Collection|BaseEvent[] $dungeonRouteCombatLogEvents
     */
    public function __construct(DungeonRoute $dungeonRoute, Collection $dungeonRouteCombatLogEvents)
    {
        $this->dungeonRoute    = $dungeonRoute;
        $this->combatLogEvents = $dungeonRouteCombatLogEvents;
//        $this->log             = App::make(DungeonRouteBuilderLoggingInterface::class);
    }


    /**
     * Find all events that are relevant for constructing a dungeon route from a combat log event list
     *
     * @return Collection|BaseResultEvent[]
     */
    public function filter(): Collection
    {
        // @TODO Fetch current mapping version, fetch enemy forces for said mapping version and filter in the query instead
        $validNpcIds = $this->dungeonRoute->dungeon->getNpcIdsWithEnemyForces();

        // All events that are necessary to build the final route
        $resultEvents = collect();
        // Keep track of our party state to detect wipes
        $partyState = new PartyState();
        // Keep track of our current pull
        $currentPull = new CurrentPull($resultEvents, $validNpcIds);

        foreach ($this->combatLogEvents as $combatLogEvent) {
            // Map changes yes please
            if ($combatLogEvent instanceof MapChange) {
                $resultEvents->push((new MapChangeResultEvent($combatLogEvent)));
                continue;
            }

            // Keep our party state up to date with relevant events
            $partyState->parse($combatLogEvent);

            // Keep our current pull up to date with relevant events
            $currentPull->parse($combatLogEvent);

            // Notify the current pull if we wiped
            if ($partyState->isPartyWiped()) {
                $currentPull->partyWiped();
            }
        }

        // Ensure events are in chronological order
        $resultEvents = $resultEvents->sortBy(function (BaseResultEvent $baseResultEvent) {
            return $baseResultEvent->getBaseEvent()->getTimestamp()->getTimestampMs();
        });

        // Verify we did everything right
        $currentPull->noMoreEvents();

        return $resultEvents;
    }
}
