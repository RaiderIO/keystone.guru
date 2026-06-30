<?php

namespace App\Jobs\CombatLog;

use App\Jobs\Logging\FetchCombatLogRunFanoutLoggingInterface;
use App\Models\Season;
use App\Service\CombatLog\Dtos\CombatLogRunContextInterface;
use App\Service\RaiderIO\Dtos\CombatLogSegment;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fetches where to find combat log data from a given run, and then either directly processes the
 * found combat log or fans out once more to process multiple combat log parts.
 */
class FetchCombatLogRunFanout implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly Season                        $season,
        private readonly int                           $runId,
        private readonly int                           $combatLogVersion,
        private readonly ?CombatLogRunContextInterface $runContext = null,
    ) {
        $this->queue = sprintf('%s-%s-combat-log-fanout', config('app.type'), config('app.env'));
    }

    public function handle(
        RaiderIOApiServiceInterface             $raiderIOApiService,
        FetchCombatLogRunFanoutLoggingInterface $log,
    ): void {
        $log->handleStart($this->runId, $this->combatLogVersion);

        try {
            $download = $raiderIOApiService->getCombatLogSegmentsForRun($this->season, $this->runId);

            if ($download === null) {
                $log->handleDownloadNotAvailable($this->runId);

                return;
            }

            foreach ($download->segments as $segment) {
                /** @var CombatLogSegment $segment */
                $log->handleDispatchingSegment($this->runId, $segment->id, $segment->downloadUrl);

                ProcessCombatLogSegmentFromUrl::dispatch(
                    $this->runId,
                    $segment->id,
                    $segment->downloadUrl,
                    $this->combatLogVersion,
                    $this->runContext,
                );
            }
        } finally {
            $log->handleEnd($this->runId);
        }
    }
}
