<?php

namespace App\Service\CombatLogEvent;

use App\Service\CombatLogEvent\Models\CombatLogEventFilter;
use App\Service\CombatLogEvent\Models\CombatLogEventSearchResult;

interface CombatLogEventServiceInterface
{
    public function getCombatLogEvents(CombatLogEventFilter $filters): CombatLogEventSearchResult;
}
