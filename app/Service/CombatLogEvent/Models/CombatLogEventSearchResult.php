<?php

namespace App\Service\CombatLogEvent\Models;

use App\Models\CombatLog\CombatLogEvent;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class CombatLogEventSearchResult implements Arrayable
{
    /**
     * @param Collection<CombatLogEvent> $combatLogEvents
     * @param int                        $dungeonRouteCount
     */
    public function __construct(
        private readonly Collection $combatLogEvents,
        private readonly int        $dungeonRouteCount
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
        return [
            'data'                => $this->combatLogEvents->map(function (CombatLogEvent $combatLogEvent) {
                return ['x' => $combatLogEvent->pos_x, 'y' => $combatLogEvent->pos_y];
            })->toArray(),
            'dungeon_route_count' => $this->dungeonRouteCount,
        ];
    }
}
