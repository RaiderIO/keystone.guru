<?php

namespace App\Service\CombatLog;

use Illuminate\Support\Collection;

interface CombatLogSplitServiceInterface
{
    public function splitCombatLogOnChallengeModes(string $filePath): Collection;
}
