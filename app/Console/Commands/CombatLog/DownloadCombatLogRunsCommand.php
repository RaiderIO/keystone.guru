<?php

namespace App\Console\Commands\CombatLog;

use App\Models\Season;
use App\Service\RaiderIO\Dtos\CombatLogSegment;
use App\Service\RaiderIO\Dtos\SearchAdvancedRun;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use App\Service\Traits\CombatLogSegmentFile;
use App\Service\Traits\Curl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Downloads Mythic+ combat log segments from Raider.IO, for local detector-validation workflows. Runs are
 * either given explicitly (--run) or found via the same search filters as `combatlog:searchruns`.
 *
 * Mirrors the resolve+download+validity-check sequence {@see \App\Jobs\CombatLog\ProcessCombatLogSegments}
 * uses: the segment download URLs are presigned and expire in ~5 minutes, so segments are resolved and
 * downloaded back-to-back per run rather than all runs being resolved up front.
 */
class DownloadCombatLogRunsCommand extends Command
{
    use Curl;
    use ResolvesCombatLogSearchFilter;
    use CombatLogSegmentFile;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:downloadruns
        {--dungeon= : Dungeon key or name filter}
        {--class=* : Character class key(s), e.g. rogue; resolves to all specs of the class}
        {--spec=* : Specific CharacterClassSpecialization ids to filter on}
        {--min-level=10 : Minimum mythic level}
        {--from-days=7 : completedAt window start, days ago}
        {--limit=20 : Maximum number of runs to search}
        {--offset=0 : Search pagination offset}
        {--output-dir=combatlogs : Directory below storage/app to download into}
        {--run=* : Explicit run id(s), skips the search}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads Mythic+ combat log segments from Raider.IO for local detector-validation workflows.';

    public function __construct(
        private readonly RaiderIOApiServiceInterface $raiderIOApiService,
        private readonly SeasonServiceInterface      $seasonService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $season = $this->seasonService->getCurrentSeason();

        if ($season === null) {
            $this->warn('combatlog:downloadruns — no current season found, skipping');

            return self::SUCCESS;
        }

        try {
            $runIds = $this->resolveRunIds($season);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (empty($runIds)) {
            $this->warn('combatlog:downloadruns — no runs to download');

            return self::SUCCESS;
        }

        $outputDir     = trim((string)$this->option('output-dir'), '/');
        $outputDirPath = storage_path(sprintf('app/%s', $outputDir));
        File::ensureDirectoryExists($outputDirPath);

        $runsOk          = 0;
        $runsFailed      = 0;
        $filesDownloaded = 0;
        $totalBytes      = 0;

        foreach ($runIds as $runId) {
            $segmentsResponse = $this->raiderIOApiService->getCombatLogSegmentsForRun($season, $runId);

            if ($segmentsResponse === null || empty($segmentsResponse->segments)) {
                $this->warn(sprintf('Run %d — no combat log segments available, skipping', $runId));
                $runsFailed++;
                continue;
            }

            $segments = $segmentsResponse->segments;
            usort($segments, fn(CombatLogSegment $a, CombatLogSegment $b): int => $a->id <=> $b->id);

            $runHadFailure = false;

            foreach ($segments as $segment) {
                $filePath = sprintf(
                    '%s/run_%d_segment_%d.%s',
                    $outputDirPath,
                    $runId,
                    $segment->id,
                    $this->resolveSegmentExtension($segment->downloadUrl),
                );

                if (!$this->downloadSegmentToFile($segment->downloadUrl, $filePath)) {
                    $this->error(sprintf('Run %d segment %d — download failed', $runId, $segment->id));
                    $runHadFailure = true;
                    continue;
                }

                if (!$this->isPlausibleSegment($filePath)) {
                    $this->error(sprintf('Run %d segment %d — downloaded file is not a combat log', $runId, $segment->id));
                    @unlink($filePath);
                    $runHadFailure = true;
                    continue;
                }

                $filesDownloaded++;
                $totalBytes += filesize($filePath);
                $this->info(sprintf('Saved %s', $filePath));
            }

            if ($runHadFailure) {
                $runsFailed++;
            } else {
                $runsOk++;
            }
        }

        $this->info(sprintf(
            'combatlog:downloadruns — runs_ok=%d runs_failed=%d files=%d total_bytes=%d',
            $runsOk,
            $runsFailed,
            $filesDownloaded,
            $totalBytes,
        ));
        $this->comment('Feed the downloaded files to: php artisan combatlog:extractdata <path>');

        return self::SUCCESS;
    }

    /**
     * Fetches a single segment to disk. Extracted as its own method (rather than calling
     * {@see Curl::curlSaveToFile()} directly from handle()) so tests can partial-mock this one network
     * boundary instead of faking HTTP end-to-end.
     */
    protected function downloadSegmentToFile(string $downloadUrl, string $filePath): bool
    {
        return $this->curlSaveToFile($downloadUrl, $filePath);
    }

    /**
     * @throws RuntimeException When a --class key is unknown, or --dungeon is ambiguous/unknown.
     * @return int[]
     */
    private function resolveRunIds(Season $season): array
    {
        $explicitRunIds = array_map(intval(...), (array)$this->option('run'));

        if (!empty($explicitRunIds)) {
            return array_values(array_unique($explicitRunIds));
        }

        $filter   = $this->buildSearchFilter($season);
        $response = $this->raiderIOApiService->searchAdvancedRuns($filter);

        return array_map(fn(SearchAdvancedRun $run): int => $run->id, $response->runs);
    }
}
