<?php

namespace App\Jobs\CombatLog;

use App\Jobs\Logging\ProcessCombatLogSegmentsLoggingInterface;
use App\Models\Season;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use App\Service\CombatLog\CombatLogParsingCriteriaServiceInterface;
use App\Service\CombatLog\CombatLogPollingHealthServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use App\Service\CombatLog\Dtos\CombatLogRunContextInterface;
use App\Service\CombatLog\Enums\CombatLogPollingFailureReason;
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
 *
 * A run that yields no data at all - no segments, an undownloadable segment, an unparsable log - stays
 * blacklisted by the parsed_combat_logs row combatlog:pollruns wrote for it, so it is never picked up
 * again. What it must not also keep is the parsing budget it was handed on dispatch: that is given back
 * here, so the next hourly poll spends it on a run that does yield data (#4173).
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

    /**
     * @param CombatLogParsingCriterionCheck[] $criteria     The criteria whose budget this run was recorded against.
     * @param string|null                      $criteriaDate The date those counts were recorded on. Carried explicitly
     *                                                       because a retried or backlogged job can end up releasing
     *                                                       after midnight, and Carbon::now() would then decrement a
     *                                                       different day's row than the one that was incremented.
     */
    public function __construct(
        private readonly Season                        $season,
        private readonly int                           $runId,
        private readonly int                           $combatLogVersion,
        private readonly ?CombatLogRunContextInterface $runContext = null,
        private readonly array                         $criteria = [],
        private readonly ?string                       $criteriaDate = null,
    ) {
        $this->queue = sprintf('%s-cl-process', config('app.type'));
    }

    public function handle(
        RaiderIOApiServiceInterface              $raiderIOApiService,
        CombatLogDataExtractionServiceInterface  $extractionService,
        CombatLogPollingHealthServiceInterface   $healthService,
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

                // Counted here rather than in RaiderIOApiService, which serves callers that have
                // nothing to do with polling (the internal API, combatlog:downloadruns): only a run
                // this job was handed is a polled run. Which flavour of nothing came back - a 404,
                // an error page, an empty segments array - is in the logs; here they are one thing.
                $healthService->recordFailure(CombatLogPollingFailureReason::SegmentsUnavailable);

                $this->releaseCriteriaBudget();

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
            // Re-throw so the job is retried instead of being reported as a permanent parse failure: this
            // covers download failures (fresh one-time URLs on retry) as well as RedisException, which
            // extends RuntimeException and is rethrown unwrapped by CombatLogService::parseCombatLog() for
            // the same reason - a dropped Redis/Valkey connection mid-parse is a transient infra blip, not
            // an unparsable line (see #3791).
            throw $e;
        } catch (Throwable $e) {
            // CombatLogParseException only wraps the real failure, so the class worth grouping and reporting on is
            // the one it wrapped.
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

            $healthService->recordFailure(CombatLogPollingFailureReason::ParseFailed);
            $this->releaseCriteriaBudget();
        } finally {
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }

            if ($result) {
                $healthService->recordSucceeded();
            }

            $log->handleEnd($this->runId, $result);
        }
    }

    /**
     * Runs once the last attempt has failed - which is every path that throws out of handle(), so
     * chiefly the download and infrastructure failures it deliberately re-throws for a retry. The
     * paths handle() swallows release their own budget inline; this covers the ones it doesn't.
     */
    public function failed(?Throwable $throwable): void
    {
        app(CombatLogPollingHealthServiceInterface::class)
            ->recordFailure(CombatLogPollingFailureReason::RunFailedAfterRetries);

        $this->releaseCriteriaBudget();
    }

    /**
     * Gives back the parsing budget this run was handed on dispatch, so the criteria it was meant to
     * satisfy are polled for again on the next hourly run. Deliberately not a retry of this run: it
     * is blacklisted, and finding a replacement immediately would mean re-querying Raider.IO from
     * inside the job for what the next poll finds on its own an hour later (#4173).
     *
     * $criteria/$criteriaDate are readonly promoted properties, so a job that was serialized onto the
     * queue before this constructor gained them is restored by SerializesModels::__unserialize() with
     * both left uninitialized - it only assigns properties present in the stored payload and never
     * falls back to the constructor's default, which only applies when the constructor actually runs.
     * Direct access would then throw "must not be accessed before initialization" (#4233); isset() is
     * the safe way to read a possibly-uninitialized typed property.
     */
    private function releaseCriteriaBudget(): void
    {
        // PHPStan sees the constructor default and assumes it always runs; a job restored from the queue
        // via SerializesModels::__unserialize() never calls it - see the docblock above.
        // @phpstan-ignore isset.initializedProperty
        if (!isset($this->criteria) || $this->criteria === [] || !isset($this->criteriaDate)) {
            return;
        }

        app(CombatLogParsingCriteriaServiceInterface::class)
            ->releaseParsed($this->combatLogVersion, $this->criteria, $this->criteriaDate);
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
