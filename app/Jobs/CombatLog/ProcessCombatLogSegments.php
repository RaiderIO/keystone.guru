<?php

namespace App\Jobs\CombatLog;

use App\Jobs\Logging\ProcessCombatLogSegmentsLoggingInterface;
use App\Models\Season;
use App\Repositories\Interfaces\CombatLog\CombatLogParseFailureRepositoryInterface;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogRunContextInterface;
use App\Service\CombatLog\Exceptions\CombatLogParseException;
use App\Service\RaiderIO\Dtos\CombatLogSegment;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use App\Service\Traits\CombatLogSegmentFile;
use App\Service\Traits\Curl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

/**
 * Resolves the Raider.IO combat log segments for a run and processes them in a single execution.
 *
 * The segment download URLs are presigned and short-lived (5 minutes), so they must be resolved and
 * downloaded within the same job run rather than handed off across the queue. Each part is processed
 * independently; they do not need to be combined into a single file.
 */
class ProcessCombatLogSegments implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Curl;
    use CombatLogSegmentFile;

    public int $timeout = 1800;

    public int $tries = 3;

    public int $uniqueFor = 7200;

    public function __construct(
        private readonly Season                        $season,
        private readonly int                           $runId,
        private readonly int                           $combatLogVersion,
        private readonly ?CombatLogRunContextInterface $runContext = null,
    ) {
        $this->queue = sprintf('%s-cl-process', config('app.type'));
    }

    public function handle(
        RaiderIOApiServiceInterface              $raiderIOApiService,
        CombatLogDataExtractionServiceInterface  $extractionService,
        CombatLogParseFailureRepositoryInterface $parseFailureRepository,
        ProcessCombatLogSegmentsLoggingInterface $log,
    ): void {
        $log->handleStart($this->runId, $this->combatLogVersion);

        /** @var string[] $tempFiles */
        $tempFiles = [];
        $result    = false;

        try {
            $segmentsResponse = $raiderIOApiService->getCombatLogSegmentsForRun($this->season, $this->runId);

            if ($segmentsResponse === null || empty($segmentsResponse->segments)) {
                $log->handleSegmentsNotAvailable($this->runId);

                return;
            }

            $segments = $segmentsResponse->segments;
            usort($segments, fn(CombatLogSegment $a, CombatLogSegment $b): int => $a->id <=> $b->id);

            // Download every part first while the presigned URLs are still fresh, then extract each.
            foreach ($segments as $segment) {
                $tempPath = sprintf(
                    '%s/run_%d_segment_%d.%s',
                    sys_get_temp_dir(),
                    $this->runId,
                    $segment->id,
                    $this->resolveSegmentExtension($segment->downloadUrl),
                );
                $log->handleDownloadingSegment($this->runId, $segment->id, $segment->downloadUrl, $tempPath);

                if (!$this->curlSaveToFile($segment->downloadUrl, $tempPath)) {
                    $log->handleSegmentDownloadFailed($this->runId, $segment->id, $tempPath);

                    throw new RuntimeException(
                        sprintf('Failed to download segment %d for run %d', $segment->id, $this->runId),
                    );
                }

                $tempFiles[] = $tempPath;

                if (!$this->isPlausibleSegment($tempPath)) {
                    $log->handleSegmentIsNotACombatLog($this->runId, $segment->id, $tempPath);

                    throw new RuntimeException(
                        sprintf('Downloaded segment %d for run %d is not a combat log', $segment->id, $this->runId),
                    );
                }
            }

            foreach ($tempFiles as $tempFile) {
                $extractionService->extractData($tempFile, runContext: $this->runContext);
            }

            $result = true;
        } catch (RuntimeException $e) {
            // Re-throw so the job is retried instead of recording a permanent CombatLogParseFailure: this
            // covers download failures (fresh one-time URLs on retry) as well as RedisException, which
            // extends RuntimeException and is rethrown unwrapped by CombatLogService::parseCombatLog() for
            // the same reason - a dropped Redis/Valkey connection mid-parse is a transient infra blip, not
            // an unparsable line (see #3791).
            throw $e;
        } catch (Throwable $e) {
            // CombatLogParseException only wraps the real failure, so the class worth grouping and reporting on is
            // the one it wrapped. Resolved once here because the log line used to report the wrapper while the
            // recorded failure reported the cause, which made the two impossible to correlate.
            $exceptionClass = $e instanceof CombatLogParseException ? $e->getOriginalExceptionClass() : $e::class;
            $lineNumber     = $e instanceof CombatLogParseException ? $e->lineNumber : null;
            $rawLine        = $e instanceof CombatLogParseException ? $e->rawLine : null;

            $log->handleParseError(
                $this->runId,
                $this->season->id,
                $this->combatLogVersion,
                $lineNumber,
                $exceptionClass,
                $e->getMessage(),
                $rawLine,
            );

            $parseFailureRepository->recordFailure(
                $this->runId,
                $this->season->id,
                $this->combatLogVersion,
                $lineNumber,
                $rawLine,
                $e->getMessage(),
                $exceptionClass,
            );
        } finally {
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
            $log->handleEnd($this->runId, $result);
        }
    }

    public function uniqueId(): string
    {
        return (string)$this->runId;
    }

    /**
     * @return int[]
     */
    public function backoff(): array
    {
        return [30, 120];
    }
}
