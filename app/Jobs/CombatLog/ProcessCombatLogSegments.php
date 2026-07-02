<?php

namespace App\Jobs\CombatLog;

use App\Jobs\Logging\ProcessCombatLogSegmentsLoggingInterface;
use App\Models\Season;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogRunContextInterface;
use App\Service\RaiderIO\Dtos\CombatLogSegment;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
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

    public int $timeout = 1800;

    public int $tries = 3;

    public int $uniqueFor = 7200;

    public function __construct(
        private readonly Season                        $season,
        private readonly int                           $runId,
        private readonly int                           $combatLogVersion,
        private readonly ?CombatLogRunContextInterface $runContext = null,
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
            }

            foreach ($tempFiles as $tempFile) {
                $extractionService->extractData($tempFile, runContext: $this->runContext);
            }

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
            $log->handleEnd($this->runId, $result);
        }
    }

    public function uniqueId(): string
    {
        return (string)$this->runId;
    }

    /**
     * Derive the file extension from the (presigned) download URL. The extraction service relies on the
     * `.zip` extension to know it must unzip the archive before parsing; without it the raw archive bytes
     * are fed to the parser. Other downloads (e.g. the `.txt.gz`-named Raider.IO segments, whose bodies
     * arrive already decompressed via the request's content encoding) are plain text and saved as `.txt`.
     */
    private function resolveSegmentExtension(string $downloadUrl): string
    {
        $path = (string)parse_url($downloadUrl, PHP_URL_PATH);

        return str_ends_with($path, '.zip') ? 'zip' : 'txt';
    }

    /**
     * @return int[]
     */
    public function backoff(): array
    {
        return [30, 120];
    }
}
