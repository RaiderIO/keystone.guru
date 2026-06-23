<?php

namespace App\Service\ChallengeModeRunData;

use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\CombatLog\CombatLogEvent;
use Illuminate\Support\Collection;

interface ChallengeModeRunDataServiceInterface
{
    public function convert(bool $force = false, ?callable $onProcess = null): bool;

    public function convertChallengeModeRunData(ChallengeModeRunData $challengeModeRunData): bool;

    public function insertAllToOpensearch(int $count = 1000, ?callable $onProcess = null): bool;

    /**
     * @param Collection<int, CombatLogEvent> $combatLogEvents
     */
    public function insertToOpensearch(
        Collection $combatLogEvents,
        int        $count = 1000,
        ?callable  $onProcess = null,
    ): bool;
}
