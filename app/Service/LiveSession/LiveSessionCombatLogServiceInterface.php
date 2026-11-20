<?php

namespace App\Service\LiveSession;

use Illuminate\Support\Collection;

interface LiveSessionCombatLogServiceInterface
{
    /**
     * Reduce a combat log to the minimal raw lines needed to keep re-running the auto-route creator across
     * incremental live-session batches, plus whatever the buffer filters retain (e.g. summons). Player
     * movement is not retained; the latest position is persisted to the database per chunk instead.
     *
     * The output is raw combat-log lines, so it can be re-fed verbatim together with the next batch of
     * lines and reduced again. In-flight combat state (enemy sightings and summons) that only becomes a
     * result event once the enemy dies is retained, so a kill spanning multiple batches is not lost.
     *
     * @param Collection<int, int> $validNpcIds
     *
     * @return array<int, string> the kept raw combat-log lines, in stream order
     */
    public function reduceCombatLogForBuffer(string $combatLogFilePath, Collection $validNpcIds): array;
}
