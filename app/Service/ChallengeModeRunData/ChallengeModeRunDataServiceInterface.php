<?php

namespace App\Service\ChallengeModeRunData;

use App\Models\CombatLog\ChallengeModeRunData;

interface ChallengeModeRunDataServiceInterface
{
    public function convert(): bool;

    public function convertChallengeModeRunData(ChallengeModeRunData $challengeModeRunData): bool;
}
