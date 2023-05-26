<?php

namespace App\Service\CombatLog;

use Illuminate\Support\Collection;

interface CombatLogServiceInterface
{
    public function parseCombatLogToEvents(string $filePath): Collection;
}
