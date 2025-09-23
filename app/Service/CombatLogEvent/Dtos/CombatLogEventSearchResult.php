<?php

namespace App\Service\CombatLogEvent\Dtos;

use App\Models\CombatLog\CombatLogEvent;
use App\Models\CombatLog\CombatLogEventDataType;
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
        private readonly int                         $dungeonRouteCount,
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
            'data' => $this->combatLogEvents->map(function (CombatLogEvent $combatLogEvent) use ($dungeon, $floors, $useFacade) {
                $ingameXY = match ($this->combatLogEventFilter->getDataType()) {
                    CombatLogEventDataType::PlayerPosition => $combatLogEvent->getIngameXY(),
                    CombatLogEventDataType::EnemyPosition  => $combatLogEvent->getIngameXYEnemy(),
                };

                // If the XY is null, we can't calculate a map location
                if ($ingameXY === null) {
                    return null;
                }

                $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(
                    $ingameXY->setFloor($floors->get($combatLogEvent->ui_map_id)),
                );

                $latLngArray = ($useFacade ?
                    $this->coordinatesService->convertMapLocationToFacadeMapLocation($dungeon->getCurrentMappingVersion(), $latLng) :
                    $latLng)->toArrayWithFloor();

                $latLngArray['lat'] = round($latLngArray['lat'], 2);
                $latLngArray['lng'] = round($latLngArray['lng'], 2);

                return $latLngArray;
            })->filter()->toArray(),
            'dungeon_route_count' => $this->dungeonRouteCount,
        ];
    }
}
