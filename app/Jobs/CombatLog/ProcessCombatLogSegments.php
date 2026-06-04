<?php

namespace App\Jobs\CombatLog;

use App\Jobs\Logging\ProcessCombatLogSegmentsLoggingInterface;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use App\Service\RaiderIO\Dtos\CombatLogSegment;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use App\Service\Traits\Curl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class ProcessCombatLogSegments implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Curl;

    public int $timeout = 1800;

    public function __construct(
        private readonly int $runId,
        private readonly int $combatLogVersion,
    ) {
        $this->queue = sprintf('%s-%s-combat-log-process', config('app.type'), config('app.env'));
    }

    public function handle(
        RaiderIOApiServiceInterface              $raiderIOApiService,
        CombatLogDataExtractionServiceInterface  $extractionService,
        ProcessCombatLogSegmentsLoggingInterface $log,
    ): void {
        $log->handleStart($this->runId, $this->combatLogVersion);

        /** @var string[] $tempFiles */
        $tempFiles    = [];
        $combinedPath = null;
        $result       = false;

        try {
            $segmentsResponse = $raiderIOApiService->getCombatLogSegmentsForRun($this->runId);

            if ($segmentsResponse === null || empty($segmentsResponse->segments)) {
                $log->handleSegmentsNotAvailable($this->runId);

                return;
            }

            $segments = $segmentsResponse->segments;
            usort($segments, fn(CombatLogSegment $a, CombatLogSegment $b): int => $a->id <=> $b->id);

            foreach ($segments as $segment) {
                $tempPath = sprintf('%s/run_%d_segment_%d.txt', sys_get_temp_dir(), $this->runId, $segment->id);
                $log->handleDownloadingSegment($this->runId, $segment->id, $segment->downloadUrl, $tempPath);

                if (!$this->curlSaveToFile($segment->downloadUrl, $tempPath)) {
                    $log->handleSegmentDownloadFailed($this->runId, $segment->id, $tempPath);

                    throw new RuntimeException(
                        sprintf('Failed to download segment %d for run %d', $segment->id, $this->runId),
                    );
                }

                $tempFiles[] = $tempPath;
            }

            $combinedPath = sprintf('%s/run_%d_combined.txt', sys_get_temp_dir(), $this->runId);
            $log->handleJoiningSegments($this->runId, count($segments), $combinedPath);

            $combined = fopen($combinedPath, 'w');
            foreach ($tempFiles as $tempFile) {
                $segmentHandle = fopen($tempFile, 'r');
                stream_copy_to_stream($segmentHandle, $combined);
                fclose($segmentHandle);
            }
            fclose($combined);

            $extractionService->extractData($combinedPath);
            $result = true;
        } catch (RuntimeException $e) {
            // Re-throw download failures so the job can be retried with fresh one-time URLs.
            throw $e;
        } catch (Throwable $e) {
            $log->handleParseError($this->runId, $this->combatLogVersion, $e->getMessage(), $e::class);
        } finally {
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
            if ($combinedPath !== null && file_exists($combinedPath)) {
                unlink($combinedPath);
            }
            $log->handleEnd($this->runId, $result);
        }
    }
}
