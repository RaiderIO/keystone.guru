<?php

namespace App\Jobs\CombatLog;

use App\Jobs\Logging\ProcessCombatLogSegmentFromUrlLoggingInterface;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogRunContextInterface;
use App\Service\Traits\Curl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class ProcessCombatLogSegmentFromUrl implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Curl;

    public int $timeout = 1800;

    public function __construct(
        private readonly int                           $runId,
        private readonly int                           $segmentId,
        private readonly string                        $downloadUrl,
        private readonly int                           $combatLogVersion,
        private readonly ?CombatLogRunContextInterface $runContext = null,
    ) {
        $this->queue = sprintf('%s-%s-combat-log-process', config('app.type'), config('app.env'));
    }

    public function handle(
        ProcessCombatLogSegmentFromUrlLoggingInterface $log,
        CombatLogDataExtractionServiceInterface        $extractionService,
    ): void {
        $log->handleStart($this->runId, $this->segmentId, $this->downloadUrl, $this->combatLogVersion);

        $tempPath = sprintf('%s/run_%d_segment_%d', sys_get_temp_dir(), $this->runId, $this->segmentId);
        $result   = false;

        try {
            if (!$this->curlSaveToFile($this->downloadUrl, $tempPath)) {
                $log->handleDownloadFailed($this->runId, $this->segmentId, $tempPath);

                throw new RuntimeException(
                    sprintf('Failed to download segment %d for run %d', $this->segmentId, $this->runId),
                );
            }

            $log->handleDownloaded($tempPath);
            $extractionService->extractData($tempPath, runContext: $this->runContext);
            $result = true;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            $log->handleParseError($this->runId, $this->combatLogVersion, $e->getMessage(), $e::class);
        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            $txtFilePath = str_replace('zip', 'txt', $tempPath);
            if ($txtFilePath !== $tempPath && file_exists($txtFilePath)) {
                unlink($txtFilePath);
            }
            $log->handleEnd($this->runId, $result);
        }
    }
}
