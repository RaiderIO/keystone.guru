<?php

namespace App\Service\CombatLogEvent;

use App\Service\CombatLogEvent\Models\CombatLogEventFilter;
use App\Service\CombatLogEvent\Models\CombatLogEventGridAggregationResult;
use App\Service\CombatLogEvent\Models\CombatLogEventSearchResult;
use Illuminate\Support\Collection;

interface CombatLogEventServiceInterface
{
    public function getCombatLogEvents(CombatLogEventFilter $filters): CombatLogEventSearchResult;

    public function getGridAggregation(CombatLogEventFilter $filters): ?CombatLogEventGridAggregationResult;

    public function getRunCount(CombatLogEventFilter $filters): int;

    public function getRunCountPerDungeon(): Collection;
}
