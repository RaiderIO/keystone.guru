<?php

namespace App\Jobs\CombatLog;

use App\Jobs\Logging\FetchCombatLogRunFanoutLoggingInterface;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

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
        private readonly int $runId,
        private readonly int $combatLogVersion,
    ) {
        $this->queue = sprintf('%s-%s-combat-log-fanout', config('app.type'), config('app.env'));
    }

    public function handle(
        RaiderIOApiServiceInterface             $raiderIOApiService,
        FetchCombatLogRunFanoutLoggingInterface $log,
    ): void {
        $log->handleStart($this->runId, $this->combatLogVersion);

        try {
            $download = $raiderIOApiService->getCombatLogForRun($this->runId);

            if ($download === null) {
                $log->handleDownloadNotAvailable($this->runId);

                return;
            }

            if ($download->isFile) {
                $log->handleDispatchingPart($this->runId, $download->diskName, $download->s3Path);

                ProcessCombatLogPart::dispatch(
                    $download->s3Bucket,
                    $download->s3Path,
                    $download->combatLogVersion,
                    $download->diskName,
                );
            } else {
                $files = Storage::disk($download->diskName)->files($download->s3Path);
                $log->handleIteratingFiles($this->runId, $download->s3Bucket, $download->s3Path, count($files));

                foreach ($files as $filePath) {
                    ProcessCombatLogPart::dispatch(
                        $download->s3Bucket,
                        $filePath,
                        $download->combatLogVersion,
                        $download->diskName,
                    );
                }
            }
        } finally {
            $log->handleEnd($this->runId);
        }
    }
}
