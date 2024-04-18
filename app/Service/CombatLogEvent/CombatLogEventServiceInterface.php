<?php

namespace App\Service\CombatLogEvent;

use App\Service\CombatLogEvent\Models\CombatLogEventFilter;
use Illuminate\Support\Collection;

interface CombatLogEventServiceInterface
{
    public function getCombatLogEvents(CombatLogEventFilter $filters): Collection;
}
