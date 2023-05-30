<?php

namespace App\Service\CombatLog\Models;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\SpecialEvents\MapChange;
use App\Models\DungeonRoute;
use App\Models\Npc;
use App\Models\NpcClassification;
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
     * @return Collection|BaseEvent[]
     */
    public function filter(): Collection
    {
        // @TODO Fetch current mapping version, fetch enemy forces for said mapping version and filter in the query instead
        $validNpcIds = $this->dungeonRoute->dungeon->npcs()->get()->filter(function (Npc $npc) {
            return $npc->classification_id === NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS] || $npc->enemyForces->enemy_forces > 0;
        })->pluck('id');

        // All events that are necessary to build the final route
        $resultEvents = collect();
        // Keep track of our party state to detect wipes
        $partyState = new PartyState();
        // Keep track of our current pull
        $currentPull = new CurrentPull($validNpcIds);

        foreach ($this->combatLogEvents as $combatLogEvent) {

            // Map changes yes please
            if ($combatLogEvent instanceof MapChange) {
                $resultEvents->push((new MapChangeResultEvent($combatLogEvent)));
                continue;
            }

            // Keep our party state up to date with relevant events
            $partyState->parse($combatLogEvent);

            // Keep our current pull up to date with relevant events
            $currentPull->parse($resultEvents, $combatLogEvent);

            // Notify the current pull if we wiped
            if ($partyState->isPartyWiped()) {
                $currentPull->partyWiped();
            }
        }

        // Ensure events are in chronological order
        $resultEvents = $resultEvents->sortBy(function(BaseResultEvent $baseResultEvent){
            return $baseResultEvent->getBaseEvent()->getTimestamp()->getTimestampMs();
        });

        // Verify we did everything right
        $currentPull->noMoreEvents();

//        dd($resultEvents->map(function (BaseEvent $event) {
//            if ($event instanceof MapChange) {
//                return $event->getUiMapName();
//            }
//            if ($event instanceof UnitDied) {
//                return sprintf('%s - %s', $event->getEventName(), $event->getGenericData()->getDestGuid());
//            }

//            $sourceGuid = $event->getGenericData()->getSourceGuid();
//            $destGuid   = $event->getGenericData()->getDestGuid();
//            if ($sourceGuid instanceof Creature) {
//                return sprintf(
//                    'source: %s -> %s @ %s,%s',
//                    $sourceGuid->getGuid(),
//                    $event->getGenericData()->getSourceName(),
//                    $event->getAdvancedData()->getPositionX(),
//                    $event->getAdvancedData()->getPositionY(),
//                );
//            } else if ($destGuid instanceof Creature) {
//                return sprintf(
//                    'dest: %s -> %s @ %s,%s',
//                    $destGuid->getGuid(),
//                    $event->getGenericData()->getDestName(),
//                    $event->getAdvancedData()->getPositionX(),
//                    $event->getAdvancedData()->getPositionY(),
//                );
//            }
//        }));

        return $resultEvents;
    }
}
