<?php

namespace App\Console\Commands\CombatLog;

use App\Logging\StructuredLogging;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcCharacteristic;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use App\Service\CombatLog\DataExtractors\DataExtractorFactoryInterface;
use App\Service\CombatLog\DataExtractors\Profiling\ProfilingDataExtractor;
use App\Service\CombatLog\DataExtractors\Profiling\ProfilingDataExtractorFactory;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use ZipArchive;

/**
 * Repeatable benchmark harness for the combat log data-extraction pipeline (#4040). Every optimization MR
 * against that pipeline must cite before/after numbers produced by this command (--json on both sides,
 * --baseline for the delta table).
 *
 * All priming and measured runs happen inside this single artisan invocation ON PURPOSE: every
 * `docker compose exec` is a fresh PHP process, so timing separate invocations would measure cold
 * JIT/opcache instead of the warmed-up steady state a long-running queue worker runs in.
 *
 * Comparability comes from priming, not transactions: the priming run(s) create every referenced NPC/spell,
 * so all measured runs take the *find* path - which is production-representative. Transactional rollback is
 * deliberately not used: Npc/Spell extend CacheModel and the model cache is not transactional, and a huge
 * transaction makes later writes progressively slower via undo growth. All runs pass force=true so the
 * ParsedCombatLog short-circuit/insert stay out of the measured work.
 */
class Benchmark extends BaseCombatLogCommand
{
    private const int SCHEMA_VERSION = 1;

    private const string MANIFEST_PATH = 'data/combatlog/benchmark_manifest.json';

    /** @var string Directory below storage/app that holds the (gitignored) corpus segments */
    private const string CORPUS_DIR = 'benchmarks';

    /**
     * @var string The 's3_combat_logs' disk (see config/filesystems.php) also durably mirrors the pinned
     *             benchmark corpus, under this prefix, so this command no longer depends on Raider.IO's
     *             segment URLs staying resolvable (#4317 - the original corpus's upstream segments expired).
     */
    private const string S3_CORPUS_PREFIX = 'benchmark-corpus/';

    /** @var float Spread between the fastest and slowest measured run above which results are flagged */
    private const float HIGH_VARIANCE_THRESHOLD_PCT = 5.0;

    private const string LOGGING_DISABLED = 'disabled';

    private const string LOGGING_DEFAULT = 'default';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:benchmark
        {--file= : Benchmark one explicit combat log file instead of the pinned corpus}
        {--run-ids= : Comma separated subset of the pinned corpus run ids}
        {--runs=3 : Number of measured runs}
        {--priming-runs=1 : Number of unmeasured priming runs before measurement}
        {--profile : Phase B - add one instrumented run with per-extractor timings and query buckets}
        {--logging=disabled : disabled or default - whether StructuredLogging is active during the runs}
        {--json= : Write the machine-readable result artifact to this path}
        {--baseline= : Previously written --json artifact to print a before/after delta against}
        {--i-know-what-im-doing : Acknowledge that this writes combat-log-derived rows to the configured database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Benchmarks the combat log data-extraction pipeline against a pinned corpus (#4040)';

    public function handle(): int
    {
        $loggingMode = (string)$this->option('logging');
        if (!in_array($loggingMode, [self::LOGGING_DISABLED, self::LOGGING_DEFAULT], true)) {
            $this->error(sprintf('Invalid --logging value "%s" - must be "disabled" or "default"', $loggingMode));

            return self::FAILURE;
        }

        $runs        = (int)$this->option('runs');
        $primingRuns = (int)$this->option('priming-runs');
        if ($runs < 1 || $primingRuns < 0) {
            $this->error('--runs must be >= 1 and --priming-runs must be >= 0');

            return self::FAILURE;
        }

        if (!$this->option('i-know-what-im-doing')) {
            $this->error('This command performs real data extraction: it writes combat-log-derived rows (npcs, spells, npc_spells, npc_characteristics, spell_dungeons, combat log events) to the configured database, and a full corpus benchmark runs for many minutes. Run it against an isolated (worktree) database and pass --i-know-what-im-doing to proceed.');

            return self::FAILURE;
        }

        $corpus = $this->resolveCorpus();
        if ($corpus === null) {
            return self::FAILURE;
        }

        $totalLines = 0;
        foreach ($corpus as $corpusRun) {
            foreach ($corpusRun['files'] as $corpusFile) {
                $totalLines += $corpusFile['lines'];
            }
        }
        $this->info(sprintf(
            'Corpus: %d run(s), %d file(s), %d lines, logging=%s, %d priming run(s), %d measured run(s)',
            count($corpus),
            array_sum(array_map(static fn(array $corpusRun) => count($corpusRun['files']), $corpus)),
            $totalLines,
            $loggingMode,
            $primingRuns,
            $runs,
        ));

        if ($loggingMode === self::LOGGING_DISABLED) {
            StructuredLogging::disable();
        }

        for ($primingRun = 1; $primingRun <= $primingRuns; $primingRun++) {
            $this->comment(sprintf('Priming run %d/%d...', $primingRun, $primingRuns));
            $this->runCorpusOnce($corpus);
        }

        // Phase A - measurement: undecorated extractors, no DB::listen, so the headline numbers carry zero
        // instrumentation cost.
        $measuredRuns = [];
        for ($measuredRun = 1; $measuredRun <= $runs; $measuredRun++) {
            $this->comment(sprintf('Measured run %d/%d...', $measuredRun, $runs));
            memory_reset_peak_usage();
            $measuredRuns[] = $this->runCorpusOnce($corpus);
        }

        $steadyState  = $this->isSteadyState($measuredRuns);
        $wallMsValues = array_map(static fn(array $run) => $run['wallNanos'] / 1e6, $measuredRuns);
        $medianWallMs = self::median($wallMsValues);
        $spreadPct    = $medianWallMs > 0 ? ((max($wallMsValues) - min($wallMsValues)) / $medianWallMs) * 100 : 0.0;
        $highVariance = $spreadPct > self::HIGH_VARIANCE_THRESHOLD_PCT;

        $phaseB = null;
        if ($this->option('profile')) {
            $this->comment('Phase B - instrumented attribution run (not comparable to Phase A)...');
            $phaseB = $this->runPhaseB($corpus);
        }

        $result = [
            'schemaVersion' => self::SCHEMA_VERSION,
            'fingerprint'   => $this->buildFingerprint($corpus, $loggingMode, $runs, $primingRuns),
            'phaseA'        => [
                'steadyState'           => $steadyState,
                'highVariance'          => $highVariance,
                'spreadPct'             => round($spreadPct, 2),
                'medianWallMs'          => round($medianWallMs, 2),
                'totalLines'            => $totalLines,
                'linesPerSecond'        => $medianWallMs > 0 ? (int)($totalLines / ($medianWallMs / 1000)) : 0,
                'medianPeakMemoryBytes' => (int)self::median(array_map(static fn(array $run) => $run['peakMemoryBytes'], $measuredRuns)),
                'measuredRuns'          => array_map(static fn(array $run) => [
                    'wallMs'          => round($run['wallNanos'] / 1e6, 2),
                    'peakMemoryBytes' => $run['peakMemoryBytes'],
                    'corpusRuns'      => array_map(static fn(array $corpusRunResult) => [
                        'wallMs'   => round($corpusRunResult['wallNanos'] / 1e6, 2),
                        'counters' => $corpusRunResult['counters'],
                    ], $run['corpusRuns']),
                ], $measuredRuns),
            ],
            'phaseB' => $phaseB,
        ];

        $this->outputPhaseA($corpus, $result['phaseA']);
        if ($phaseB !== null) {
            $this->outputPhaseB($phaseB);
        }

        if (!$steadyState) {
            $this->error('STEADY STATE NOT REACHED - the measured runs did different work; their counters are not identical. Raise --priming-runs and investigate which extractor has not converged (compare the counters in the JSON artifact).');
        }

        if ($highVariance) {
            $this->warn(sprintf('HIGH VARIANCE - spread between fastest and slowest measured run is %.2f%% (threshold %.1f%%). Wins smaller than the spread are not evidence.', $spreadPct, self::HIGH_VARIANCE_THRESHOLD_PCT));
        }

        $jsonPath = $this->option('json');
        if ($jsonPath !== null) {
            File::put($jsonPath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
            $this->info(sprintf('Wrote JSON artifact to %s', $jsonPath));
        }

        $baselinePath = $this->option('baseline');
        if ($baselinePath !== null && !$this->compareAgainstBaseline($baselinePath, $result)) {
            return self::FAILURE;
        }

        return $steadyState ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Resolves the corpus to benchmark: either one explicit --file, or the pinned manifest corpus
     * (optionally narrowed by --run-ids), downloading and hash-verifying segments as needed.
     *
     * Files are materialized to plain .txt OUTSIDE all timing - parseCombatLog() decompresses .zip files
     * inside what would otherwise be the timed region, which would swamp any real win.
     *
     * @return array<string, array{files: array<int, array{path: string, sha256: string, lines: int}>}>|null
     */
    private function resolveCorpus(): ?array
    {
        $filePath = $this->option('file');
        if ($filePath !== null) {
            if (is_dir($filePath)) {
                $this->error(sprintf('%s is a directory - refusing to benchmark it. Directories can contain non-combat-log files (e.g. the tests/CombatLogs/*_events.txt expected-output fixtures) that would be fed straight to the parser. Pass one combat log file explicitly.', $filePath));

                return null;
            }

            if (!is_file($filePath)) {
                $this->error(sprintf('File %s does not exist', $filePath));

                return null;
            }

            $materializedPath = $this->materializeFile($filePath);

            return [
                basename($filePath) => [
                    'files' => [
                        [
                            'path'   => $materializedPath,
                            'sha256' => hash_file('sha256', $filePath),
                            'lines'  => self::countLines($materializedPath),
                        ],
                    ],
                ],
            ];
        }

        $manifestPath = database_path(self::MANIFEST_PATH);
        if (!is_file($manifestPath)) {
            $this->error(sprintf('Benchmark manifest %s does not exist', $manifestPath));

            return null;
        }

        /** @var array{schemaVersion: int, runs: array<string, array{reason: string, segments: array<string, string>}>} $manifest */
        $manifest = json_decode(File::get($manifestPath), true);

        $runIds = array_keys($manifest['runs']);
        if ($this->option('run-ids') !== null) {
            $runIds = array_map(trim(...), explode(',', (string)$this->option('run-ids')));
            foreach ($runIds as $runId) {
                if (!isset($manifest['runs'][$runId])) {
                    $this->error(sprintf('Run id %s is not part of the pinned corpus (%s)', $runId, implode(', ', array_keys($manifest['runs']))));

                    return null;
                }
            }
        }

        $corpusDirPath = storage_path(sprintf('app/%s', self::CORPUS_DIR));
        File::ensureDirectoryExists($corpusDirPath);

        // Download any missing segments first - from the durable S3 mirror, not Raider.IO's segment URLs
        // (which are presigned, expire in ~5 minutes, and can vanish upstream entirely - #4317). Downloading
        // and hash-checking are strictly separate from all timing regardless of source.
        $missingFileNames = [];
        foreach ($runIds as $runId) {
            foreach (array_keys($manifest['runs'][$runId]['segments']) as $fileName) {
                if (!is_file(sprintf('%s/%s', $corpusDirPath, $fileName))) {
                    $missingFileNames[] = $fileName;
                }
            }
        }

        if (!empty($missingFileNames)) {
            $this->info(sprintf('Downloading %d missing corpus segment(s) from the S3 benchmark corpus mirror...', count($missingFileNames)));
            $s3Disk = Storage::disk('s3_combat_logs');
            foreach ($missingFileNames as $fileName) {
                $s3Path = self::S3_CORPUS_PREFIX . $fileName;
                if (!$s3Disk->exists($s3Path)) {
                    $this->error(sprintf('Corpus segment %s is not present on the S3 benchmark corpus mirror - the corpus needs re-uploading (see database/data/combatlog/benchmark_manifest.json).', $s3Path));

                    continue;
                }

                File::put(sprintf('%s/%s', $corpusDirPath, $fileName), $s3Disk->get($s3Path));
            }
        }

        $corpus = [];
        foreach ($runIds as $runId) {
            $files = [];
            foreach ($manifest['runs'][$runId]['segments'] as $fileName => $expectedSha256) {
                $segmentPath = sprintf('%s/%s', $corpusDirPath, $fileName);

                if (!is_file($segmentPath)) {
                    $this->error(sprintf('Corpus segment %s is still missing after download from the S3 mirror. The corpus needs re-uploading (new run ids + manifest + S3 objects).', $segmentPath));

                    return null;
                }

                $actualSha256 = hash_file('sha256', $segmentPath);
                if ($actualSha256 !== $expectedSha256) {
                    $this->error(sprintf('Corpus segment %s does not match the manifest (expected sha256 %s, got %s). Benchmarks against a divergent corpus are not comparable - delete the file to re-download it, or re-pin the corpus.', $segmentPath, $expectedSha256, $actualSha256));

                    return null;
                }

                $materializedPath = $this->materializeFile($segmentPath);

                $files[] = [
                    'path'   => $materializedPath,
                    'sha256' => $expectedSha256,
                    'lines'  => self::countLines($materializedPath),
                ];
            }

            $corpus[sprintf('run_%s', $runId)] = ['files' => $files];
        }

        return $corpus;
    }

    /**
     * Returns a plain-text path for the given combat log file, extracting .zip files once so decompression
     * never happens inside the timed region ({@see \App\Service\CombatLog\CombatLogService::parseCombatLog}
     * calls extractCombatLog() on entry, which would put it there).
     */
    private function materializeFile(string $filePath): string
    {
        if (!str_ends_with($filePath, '.zip')) {
            return $filePath;
        }

        $materializedDirPath = storage_path(sprintf('app/%s/materialized', self::CORPUS_DIR));
        File::ensureDirectoryExists($materializedDirPath);

        $materializedPath = sprintf('%s/%s.txt', $materializedDirPath, pathinfo($filePath, PATHINFO_FILENAME));
        if (is_file($materializedPath)) {
            return $materializedPath;
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new InvalidArgumentException(sprintf('%s is not a valid .zip file', $filePath));
        }

        try {
            $entryName = $zip->getNameIndex(0);
            if ($entryName === false) {
                throw new InvalidArgumentException(sprintf('%s does not contain any entries', $filePath));
            }

            File::put($materializedPath, $zip->getFromName($entryName));
        } finally {
            $zip->close();
        }

        return $materializedPath;
    }

    /**
     * Runs the full corpus through the extraction pipeline once, mirroring production: one fresh extraction
     * service (and thus one fresh extractor set with its accumulating per-run state) per corpus run, reused
     * across all of that run's segments - exactly like {@see \App\Jobs\CombatLog\ProcessCombatLogSegments}.
     *
     * @param array<string, array{files: array<int, array{path: string, sha256: string, lines: int}>}> $corpus
     *
     * @return array{wallNanos: int, peakMemoryBytes: int, corpusRuns: array<string, array{wallNanos: int, counters: array<string, int>}>}
     */
    private function runCorpusOnce(array $corpus): array
    {
        $corpusRunResults = [];
        $totalNanos       = 0;

        foreach ($corpus as $label => $corpusRun) {
            /** @var CombatLogDataExtractionServiceInterface $extractionService */
            $extractionService = $this->laravel->make(CombatLogDataExtractionServiceInterface::class);

            $counters   = [];
            $startNanos = hrtime(true);
            foreach ($corpusRun['files'] as $corpusFile) {
                $result = $extractionService->extractData($corpusFile['path'], true);

                foreach ($result?->toArray() ?? [] as $key => $value) {
                    $counters[$key] = ($counters[$key] ?? 0) + $value;
                }
            }
            $wallNanos = hrtime(true) - $startNanos;

            $corpusRunResults[$label] = [
                'wallNanos' => $wallNanos,
                'counters'  => $counters,
            ];
            $totalNanos += $wallNanos;
        }

        return [
            'wallNanos'       => $totalNanos,
            'peakMemoryBytes' => memory_get_peak_usage(true),
            'corpusRuns'      => $corpusRunResults,
        ];
    }

    /**
     * Phase B - attribution: decorated extractors plus DB::listen. Wall time from this phase is reported
     * separately and never compared to Phase A; DB::listen is far more expensive per query than hrtime() is
     * per call, so it must never be active during measurement.
     *
     * @param array<string, array{files: array<int, array{path: string, sha256: string, lines: int}>}> $corpus
     *
     * @return array{wallMs: float, extractors: array<int, array{class: string, calls: int, totalMs: float, pct: float, lifecycle: array<string, array{totalMs: float, calls: int}>}>, queries: array<int, array{connection: string, sql: string, count: int, totalMs: float}>}
     */
    private function runPhaseB(array $corpus): array
    {
        $profilingFactory = new ProfilingDataExtractorFactory($this->laravel->make(DataExtractorFactoryInterface::class));
        $this->laravel->instance(DataExtractorFactoryInterface::class, $profilingFactory);

        try {
            return $this->runInstrumented($corpus, $profilingFactory);
        } finally {
            // Drop the profiling instance so the container falls back to the provider's undecorated binding -
            // this process is usually done, but in-process invocations (tests) must not keep resolving
            // decorated extractors
            $this->laravel->forgetInstance(DataExtractorFactoryInterface::class);
        }
    }

    /**
     * @param array<string, array{files: array<int, array{path: string, sha256: string, lines: int}>}> $corpus
     *
     * @return array{wallMs: float, extractors: array<int, array{class: string, calls: int, totalMs: float, pct: float, lifecycle: array<string, array{totalMs: float, calls: int}>}>, queries: array<int, array{connection: string, sql: string, count: int, totalMs: float}>}
     */
    private function runInstrumented(array $corpus, ProfilingDataExtractorFactory $profilingFactory): array
    {
        /** @var array<string, array{connection: string, sql: string, count: int, totalMs: float}> $queryBuckets */
        $queryBuckets = [];
        $onQuery      = static function (QueryExecuted $query) use (&$queryBuckets): void {
            $normalizedSql = self::normalizeSql($query->sql);
            $bucketKey     = sprintf('%s|%s', $query->connectionName, $normalizedSql);

            $queryBuckets[$bucketKey] ??= [
                'connection' => $query->connectionName,
                'sql'        => $normalizedSql,
                'count'      => 0,
                'totalMs'    => 0.0,
            ];
            $queryBuckets[$bucketKey]['count']++;
            $queryBuckets[$bucketKey]['totalMs'] += $query->time;
        };

        // One registration catches every connection: Connection::listen() registers on the application's
        // shared event dispatcher (QueryExecuted carries the connection name), so registering per connection
        // would double-count every query.
        DB::listen($onQuery);

        $phaseBRun = $this->runCorpusOnce($corpus);

        $extractorTotals = [];
        foreach ($profilingFactory->getCreatedExtractors() as $profilingDataExtractor) {
            /** @var ProfilingDataExtractor $profilingDataExtractor */
            $class = $profilingDataExtractor->getDataExtractor()::class;

            $extractorTotals[$class] ??= ['class' => $class, 'calls' => 0, 'totalNanos' => 0, 'lifecycle' => []];
            $extractorTotals[$class]['calls'] += $profilingDataExtractor->getTotalCalls();
            $extractorTotals[$class]['totalNanos'] += $profilingDataExtractor->getTotalNanos();

            // Keep the per-lifecycle split: beforeExtract/afterExtract fire once per file regardless of the
            // log's contents, so extractData's own call count is the only signal that events actually
            // reached the extractors
            foreach ($profilingDataExtractor->getTimings() as $lifecycleMethod => $timing) {
                $extractorTotals[$class]['lifecycle'][$lifecycleMethod] ??= ['nanos' => 0, 'calls' => 0];
                $extractorTotals[$class]['lifecycle'][$lifecycleMethod]['nanos'] += $timing['nanos'];
                $extractorTotals[$class]['lifecycle'][$lifecycleMethod]['calls'] += $timing['calls'];
            }
        }

        $allExtractorNanos = array_sum(array_column($extractorTotals, 'totalNanos'));

        $extractors = array_values(array_map(static fn(array $totals) => [
            'class'     => $totals['class'],
            'calls'     => $totals['calls'],
            'totalMs'   => round($totals['totalNanos'] / 1e6, 2),
            'pct'       => $allExtractorNanos > 0 ? round($totals['totalNanos'] / $allExtractorNanos * 100, 2) : 0.0,
            'lifecycle' => array_map(static fn(array $timing) => [
                'totalMs' => round($timing['nanos'] / 1e6, 2),
                'calls'   => $timing['calls'],
            ], $totals['lifecycle']),
        ], $extractorTotals));
        usort($extractors, static fn(array $a, array $b) => $b['totalMs'] <=> $a['totalMs']);

        $queries = array_values($queryBuckets);
        usort($queries, static fn(array $a, array $b) => $b['totalMs'] <=> $a['totalMs']);
        $queries = array_map(static fn(array $bucket) => [
            'connection' => $bucket['connection'],
            'sql'        => $bucket['sql'],
            'count'      => $bucket['count'],
            'totalMs'    => round($bucket['totalMs'], 2),
        ], $queries);

        return [
            'wallMs'     => round($phaseBRun['wallNanos'] / 1e6, 2),
            'extractors' => $extractors,
            'queries'    => $queries,
        ];
    }

    /**
     * The measured runs must all have done identical work - if the counters differ between any two runs the
     * pipeline had not reached its steady state and the timings are not comparable.
     *
     * @param array<int, array{wallNanos: int, peakMemoryBytes: int, corpusRuns: array<string, array{wallNanos: int, counters: array<string, int>}>}> $measuredRuns
     */
    private function isSteadyState(array $measuredRuns): bool
    {
        $firstRunCounters = array_map(static fn(array $corpusRunResult) => $corpusRunResult['counters'], $measuredRuns[0]['corpusRuns']);

        foreach ($measuredRuns as $measuredRun) {
            if (array_map(static fn(array $corpusRunResult) => $corpusRunResult['counters'], $measuredRun['corpusRuns']) !== $firstRunCounters) {
                return false;
            }
        }

        return true;
    }

    /**
     * Everything that must be equal between two benchmark artifacts for their delta to be meaningful,
     * plus the git state for provenance (git state is exempt from the comparability check - comparing
     * before/after a code change is the whole point).
     *
     * @param array<string, array{files: array<int, array{path: string, sha256: string, lines: int}>}> $corpus
     *
     * @return array<string, mixed>
     */
    private function buildFingerprint(array $corpus, string $loggingMode, int $runs, int $primingRuns): array
    {
        $corpusHashes = [];
        foreach ($corpus as $corpusRun) {
            foreach ($corpusRun['files'] as $corpusFile) {
                $corpusHashes[basename($corpusFile['path'])] = $corpusFile['sha256'];
            }
        }

        return [
            'git' => [
                'commit' => trim((string)shell_exec(sprintf('git -C %s rev-parse HEAD 2>/dev/null', escapeshellarg(base_path())))) ?: null,
                'branch' => trim((string)shell_exec(sprintf('git -C %s rev-parse --abbrev-ref HEAD 2>/dev/null', escapeshellarg(base_path())))) ?: null,
                'dirty'  => trim((string)shell_exec(sprintf('git -C %s status --porcelain 2>/dev/null', escapeshellarg(base_path())))) !== '',
            ],
            'phpVersion'           => PHP_VERSION,
            'opcacheJit'           => (string)ini_get('opcache.jit'),
            'opcacheJitBufferSize' => (string)ini_get('opcache.jit_buffer_size'),
            'loggingMode'          => $loggingMode,
            'runs'                 => $runs,
            'primingRuns'          => $primingRuns,
            'corpus'               => $corpusHashes,
            'tableRowCounts'       => [
                'npcs'                    => Npc::count(),
                'npc_spells'              => NpcSpell::count(),
                'npc_characteristics'     => NpcCharacteristic::count(),
                'spells'                  => Spell::count(),
                'spell_dungeons'          => SpellDungeon::count(),
                'combat_log_npc_events'   => CombatLogNpcEvent::count(),
                'combat_log_spell_events' => CombatLogSpellEvent::count(),
            ],
        ];
    }

    /**
     * @param array<string, array{files: array<int, array{path: string, sha256: string, lines: int}>}> $corpus
     * @param array<string, mixed>                                                                     $phaseA
     */
    private function outputPhaseA(array $corpus, array $phaseA): void
    {
        $rows = [];
        foreach (array_keys($corpus) as $label) {
            $corpusRunWallMsValues = array_map(static fn(array $measuredRun) => $measuredRun['corpusRuns'][$label]['wallMs'], $phaseA['measuredRuns']);
            $lines                 = array_sum(array_map(static fn(array $corpusFile) => $corpusFile['lines'], $corpus[$label]['files']));
            $medianWallMs          = self::median($corpusRunWallMsValues);

            $rows[] = [
                $label,
                count($corpus[$label]['files']),
                number_format($lines),
                number_format($medianWallMs, 1),
                number_format($medianWallMs > 0 ? (int)($lines / ($medianWallMs / 1000)) : 0),
            ];
        }

        $rows[] = [
            'TOTAL (aggregate)',
            array_sum(array_map(static fn(array $corpusRun) => count($corpusRun['files']), $corpus)),
            number_format($phaseA['totalLines']),
            number_format($phaseA['medianWallMs'], 1),
            number_format($phaseA['linesPerSecond']),
        ];

        $this->info('');
        $this->info('Phase A - measurement (median across measured runs):');
        $this->table(['Run', 'Files', 'Lines', 'Median wall ms', 'Lines/sec'], $rows);
        $this->info(sprintf(
            'Spread: %.2f%% | Peak memory (median): %.1f MB | Steady state: %s',
            $phaseA['spreadPct'],
            $phaseA['medianPeakMemoryBytes'] / 1024 / 1024,
            $phaseA['steadyState'] ? 'yes' : 'NO',
        ));
    }

    /**
     * @param array{wallMs: float, extractors: array<int, array{class: string, calls: int, totalMs: float, pct: float, lifecycle: array<string, array{totalMs: float, calls: int}>}>, queries: array<int, array{connection: string, sql: string, count: int, totalMs: float}>} $phaseB
     */
    private function outputPhaseB(array $phaseB): void
    {
        $this->info('');
        $this->info(sprintf('Phase B - attribution, wall time %.1f ms (instrumented - not comparable to Phase A):', $phaseB['wallMs']));

        $this->table(
            ['Extractor', 'Calls', 'Total ms', '% of extractor time'],
            array_map(static fn(array $extractor) => [
                class_basename($extractor['class']),
                number_format($extractor['calls']),
                number_format($extractor['totalMs'], 1),
                number_format($extractor['pct'], 2),
            ], $phaseB['extractors']),
        );

        $this->table(
            ['Connection', 'Query shape', 'Count', 'Total ms'],
            array_map(static fn(array $query) => [
                $query['connection'],
                mb_strimwidth($query['sql'], 0, 100, '...'),
                number_format($query['count']),
                number_format($query['totalMs'], 1),
            ], array_slice($phaseB['queries'], 0, 15)),
        );
        if (count($phaseB['queries']) > 15) {
            $this->comment(sprintf('(%d more query shapes in the JSON artifact)', count($phaseB['queries']) - 15));
        }
    }

    /**
     * Prints the before/after delta table against a previous --json artifact. Hard-fails when the two sides
     * are not comparable (fingerprint mismatch outside git state) and refuses to present a delta as evidence
     * when either side is not steady-state or has high variance.
     *
     * @param array<string, mixed> $current
     */
    private function compareAgainstBaseline(string $baselinePath, array $current): bool
    {
        if (!is_file($baselinePath)) {
            $this->error(sprintf('Baseline file %s does not exist', $baselinePath));

            return false;
        }

        /** @var array<string, mixed>|null $baseline */
        $baseline = json_decode(File::get($baselinePath), true);
        if ($baseline === null || !isset($baseline['schemaVersion'], $baseline['fingerprint'], $baseline['phaseA'])) {
            $this->error(sprintf('Baseline file %s is not a valid benchmark artifact', $baselinePath));

            return false;
        }

        if ($baseline['schemaVersion'] !== self::SCHEMA_VERSION) {
            $this->error(sprintf('Baseline schema version %s does not match current %d', $baseline['schemaVersion'], self::SCHEMA_VERSION));

            return false;
        }

        // Git state is exempt - comparing across commits is the whole point of a baseline.
        $baselineFingerprint = $baseline['fingerprint'];
        $currentFingerprint  = $current['fingerprint'];
        unset($baselineFingerprint['git'], $currentFingerprint['git']);

        foreach ($currentFingerprint as $key => $value) {
            if (($baselineFingerprint[$key] ?? null) !== $value) {
                $this->error(sprintf(
                    "Fingerprint mismatch on \"%s\" - the two sides are not comparable.\nBaseline: %s\nCurrent:  %s",
                    $key,
                    json_encode($baselineFingerprint[$key] ?? null),
                    json_encode($value),
                ));

                return false;
            }
        }

        $rows = [];
        foreach ([
            'medianWallMs'          => 'Median wall ms',
            'linesPerSecond'        => 'Lines/sec',
            'medianPeakMemoryBytes' => 'Peak memory bytes (median)',
        ] as $key => $metricLabel) {
            $baselineValue = $baseline['phaseA'][$key];
            $currentValue  = $current['phaseA'][$key];
            $deltaPct      = $baselineValue != 0 ? (($currentValue - $baselineValue) / $baselineValue) * 100 : 0.0;

            $rows[] = [
                $metricLabel,
                number_format($baselineValue),
                number_format($currentValue),
                sprintf('%+.2f%%', $deltaPct),
            ];
        }

        $this->info('');
        $this->info(sprintf('Delta vs baseline %s (git %s -> %s):', $baselinePath, $baseline['fingerprint']['git']['commit'] ?? 'unknown', $current['fingerprint']['git']['commit'] ?? 'unknown'));
        $this->table(['Metric', 'Baseline', 'Current', 'Delta'], $rows);

        $sidesInvalid = [];
        foreach (['baseline' => $baseline['phaseA'], 'current' => $current['phaseA']] as $side => $phaseA) {
            if (!$phaseA['steadyState'] || $phaseA['highVariance']) {
                $sidesInvalid[] = sprintf('%s (steadyState=%s, highVariance=%s)', $side, json_encode($phaseA['steadyState']), json_encode($phaseA['highVariance']));
            }
        }

        // A single measured run makes both validity guards inert: steady state compares the run against
        // itself and the spread over one value is zero. Evidence needs at least two measured runs.
        if ($current['fingerprint']['runs'] < 2) {
            $sidesInvalid[] = sprintf('both sides (--runs=%d - single-run artifacts have no steady-state or variance backing)', $current['fingerprint']['runs']);
        }

        if (!empty($sidesInvalid)) {
            $this->error(sprintf('THIS DELTA IS NOT EVIDENCE - %s did not produce a valid measurement. Fix the variance/steady-state problem and re-run both sides.', implode(' and ', $sidesInvalid)));

            return false;
        }

        return true;
    }

    /**
     * Collapses variable-length IN (...) lists so queries bucket by shape rather than by parameter count.
     * Covers both placeholder lists (in (?, ?, ?)) and the literal integer lists whereIntegerInRaw inlines
     * (in (15, 23, 42)).
     */
    private static function normalizeSql(string $sql): string
    {
        return (string)preg_replace('/in \((\s*(\?|\d+)\s*,?)+\)/i', 'in (?)', $sql);
    }

    private static function countLines(string $filePath): int
    {
        $lines  = 0;
        $handle = fopen($filePath, 'r');

        try {
            while (fgets($handle) !== false) {
                $lines++;
            }
        } finally {
            fclose($handle);
        }

        return $lines;
    }

    /**
     * @param array<int, float|int> $values
     */
    private static function median(array $values): float
    {
        sort($values);
        $count = count($values);

        return $count % 2 === 1
            ? (float)$values[intdiv($count, 2)]
            : ($values[intdiv($count, 2) - 1] + $values[intdiv($count, 2)]) / 2;
    }
}
