<?php

namespace App\Service\CombatLogEvent\Models;

use App\Models\CombatLog\CombatLogEvent;
use App\Models\Floor\Floor;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

class CombatLogEventSearchResult
{
    /**
     * @param Collection<CombatLogEvent> $combatLogEvents
     */
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        private readonly CombatLogEventFilter        $combatLogEventFilter,
        private readonly Collection                  $combatLogEvents,
        private readonly int                         $dungeonRouteCount
    ) {

    }

    /**
     * @return Collection<CombatLogEvent>
     */
    public function getCombatLogEvents(): Collection
    {
        return $this->combatLogEvents;
    }

    public function getDungeonRouteCount(): int
    {
        return $this->dungeonRouteCount;
    }

    public function toArray(): array
    {
        $dungeon = $this->combatLogEventFilter->getDungeon();
        /** @var Collection<Floor> $floors */
        $floors = $dungeon->floors->keyBy('ui_map_id');

        $useFacade = User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE;

        return [
            'data'                => $this->combatLogEvents->map(function (CombatLogEvent $combatLogEvent)
            use ($dungeon, $floors, $useFacade) {
                $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(
                    $combatLogEvent->getIngameXY()->setFloor($floors->get($combatLogEvent->ui_map_id))
                );

                $latLngArray = ($useFacade ?
                    $this->coordinatesService->convertMapLocationToFacadeMapLocation($dungeon->currentMappingVersion, $latLng) :
                    $latLng)->toArrayWithFloor();

                $latLngArray['lat'] = round($latLngArray['lat'], 2);
                $latLngArray['lng'] = round($latLngArray['lng'], 2);

                return $latLngArray;
            })->toArray(),
            'dungeon_route_count' => $this->dungeonRouteCount,
        ];
    }
}
