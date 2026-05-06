<?php

namespace App\Service\CombatLogEvent;

use App\Models\CombatLog\CombatLogEvent;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\Season;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\CombatLogEvent\Dtos\CombatLogEventGridAggregationResult;
use App\Service\CombatLogEvent\Dtos\CombatLogEventSearchResult;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

interface CombatLogEventServiceInterface
{
    public function getCombatLogEvents(CombatLogEventFilter $filters): CombatLogEventSearchResult;

    public function getGridAggregation(CombatLogEventFilter $filters): ?CombatLogEventGridAggregationResult;

    public function getRunCount(CombatLogEventFilter $filters): int;

    public function getRunCountPerDungeon(): Collection;

    public function getAvailableDateRange(CombatLogEventFilter $filters): ?CarbonPeriod;

    /** @return Collection<CombatLogEvent> */
    public function generateCombatLogEvents(
        Season                  $season,
        CombatLogEventEventType $type,
        int                     $runCount = 1,
        int                     $eventsPerRun = 5,
        ?Dungeon                $dungeon = null,
    ): Collection;
}
