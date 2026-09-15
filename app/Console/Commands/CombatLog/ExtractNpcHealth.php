<?php

namespace App\Console\Commands\CombatLog;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\DataExtractors\DataExtractorFactoryInterface;
use App\Service\CombatLog\DataExtractors\DataExtractorInterface;
use App\Service\CombatLog\DataExtractors\FixedDataExtractorFactory;
use App\Service\CombatLog\DataExtractors\NpcHealthDataExtractor;
use App\Service\CombatLog\Dtos\CombatLogRunContext;
use App\Service\CombatLog\Dtos\CombatLogRunContextInterface;
use App\Service\CombatLog\Dtos\DataExtraction\NpcHealthChange;
use App\Service\CombatLog\Dtos\DataExtraction\NpcHealthObservation;
use App\Service\CombatLog\NpcHealthExtractionServiceInterface;
use Illuminate\Support\Collection;

class ExtractNpcHealth extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:extractnpchealth
                            {filePath : A combat log file, or a directory of them (e.g. the segments of one combatlog:downloadruns run)}
                            {--game-version=retail : Key of the game version whose npc_healths rows to write}
                            {--key-level= : Key level of the run, when no file contains its CHALLENGE_MODE_START}
                            {--affix-ids= : Comma separated affix ids of the run, when no file contains its CHALLENGE_MODE_START}
                            {--overwrite : Also replace real health values that differ from the observed one (default: only missing/placeholder rows)}
                            {--dry-run : Only print the comparison, write nothing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads NPC max health from a combat log, reverses the key level scaling and writes the base health to npc_healths (#4094).';

    /**
     * Execute the console command.
     */
    public function handle(
        NpcHealthExtractionServiceInterface $npcHealthExtractionService,
        CombatLogServiceInterface           $combatLogService,
    ): int {
        $filePath  = $this->argument('filePath');
        $overwrite = (bool)$this->option('overwrite');
        $dryRun    = (bool)$this->option('dry-run');

        $gameVersion = GameVersion::firstWhere('key', $this->option('game-version'));
        if ($gameVersion === null) {
            $this->error(sprintf('Unknown game version "%s"', $this->option('game-version')));

            return 1;
        }

        $runContext = $this->resolveRunContext($combatLogService, $filePath);
        if ($runContext === null) {
            $this->error('Unable to determine the key level: no CHALLENGE_MODE_START in any of the files - pass --key-level and --affix-ids');

            return 1;
        }

        $this->info(sprintf('Run context: key level %d, affix ids [%s]', $runContext->getKeyLevel(), implode(', ', $runContext->getAffixIds())));

        $observations = $this->collectObservations($filePath, $runContext);
        if ($observations->isEmpty()) {
            $this->warn('No NPC health observations found - is this an advanced combat log of a challenge mode?');

            return 1;
        }

        $dungeonIds = $observations->map(static fn(NpcHealthObservation $observation) => $observation->dungeonId)->unique();
        if ($dungeonIds->count() > 1) {
            $this->error(sprintf('Observations span multiple dungeons (ids %s) - pass the files of one dungeon at a time', $dungeonIds->implode(', ')));

            return 1;
        }

        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::findOrFail($dungeonIds->first());
        if (!$dungeon->hasMappingVersionForGameVersion($gameVersion)) {
            $this->error(sprintf('Dungeon %s has no mapping version for game version "%s"', __($dungeon->name, [], 'en_US'), $gameVersion->key));

            return 1;
        }

        $changes = $npcHealthExtractionService->compareNpcHealths($observations, $dungeon, $gameVersion);

        $observedNpcIds = $observations->map(static fn(NpcHealthObservation $observation) => $observation->npcId)->unique()->sort()->values();
        $ignoredNpcIds  = $observedNpcIds->diff($changes->keys());

        $this->info(sprintf(
            'Dungeon: %s, observed %d distinct NPC ids of which %d are NPCs of this dungeon%s',
            __($dungeon->name, [], 'en_US'),
            $observedNpcIds->count(),
            $changes->count(),
            $ignoredNpcIds->isEmpty() ? '' : sprintf(' (ignored: %s)', $ignoredNpcIds->implode(', ')),
        ));

        $this->printChanges($changes, $overwrite);

        if ($dryRun) {
            $this->comment('Dry run - nothing was written');

            return 0;
        }

        $written = $npcHealthExtractionService->applyNpcHealths($changes, $gameVersion, $overwrite);

        $this->info(sprintf('Wrote %d npc_healths row(s). Run `php artisan mapping:save` to persist them to the seeder files.', $written));

        return 0;
    }

    /**
     * The key level and affixes the max HP values were scaled by. A Raider.IO run is split into segment files and only
     * the first carries the CHALLENGE_MODE_START - the others open with a RIO_LOG_VERSION header that names the
     * dungeon but not the level, which the extraction service then takes from the run context (exactly what the
     * ingestion jobs pass in from the Raider.IO run). Taken from the --key-level/--affix-ids options when given,
     * otherwise from the first CHALLENGE_MODE_START found in the files.
     */
    private function resolveRunContext(CombatLogServiceInterface $combatLogService, string $filePath): ?CombatLogRunContextInterface
    {
        if ($this->option('key-level') !== null) {
            $affixIds = array_values(array_map('intval', array_filter(explode(',', (string)$this->option('affix-ids')))));

            return new CombatLogRunContext((int)$this->option('key-level'), $affixIds);
        }

        $result = null;

        $this->parseCombatLogRecursively($filePath, static function (string $filePath) use ($combatLogService, &$result) {
            $combatLogService->parseCombatLog($filePath, static function (
                int    $combatLogVersion,
                bool   $advancedLoggingEnabled,
                string $rawEvent,
            ) use (&$result) {
                // Cheap string check first - this pass only needs one line out of the whole file
                if ($result !== null || !str_contains($rawEvent, SpecialEvent::SPECIAL_EVENT_CHALLENGE_MODE_START)) {
                    return null;
                }

                $parsedEvent = new CombatLogEntry($rawEvent)->parseEvent([], $combatLogVersion);
                if ($parsedEvent instanceof ChallengeModeStart) {
                    $result = new CombatLogRunContext($parsedEvent->getKeystoneLevel(), $parsedEvent->getAffixIDs() ?? []);
                }

                return $parsedEvent;
            });

            // A non-zero result stops the recursion, which is what we want once the context is known
            return $result === null ? 0 : 1;
        });

        return $result;
    }

    /**
     * Runs the combat log(s) through the extraction pipeline with only the health extractor installed.
     *
     * @return Collection<string, NpcHealthObservation>
     */
    private function collectObservations(string $filePath, CombatLogRunContextInterface $runContext): Collection
    {
        $extractor = new NpcHealthDataExtractor();

        /** @var Collection<int, DataExtractorInterface> $extractors */
        $extractors = collect([$extractor]);

        $this->laravel->instance(DataExtractorFactoryInterface::class, new FixedDataExtractorFactory($extractors));

        try {
            /** @var CombatLogDataExtractionServiceInterface $extractionService */
            $extractionService = $this->laravel->make(CombatLogDataExtractionServiceInterface::class);

            $this->parseCombatLogRecursively($filePath, function (string $filePath) use ($extractionService, $runContext) {
                $this->info(sprintf('Parsing file %s', $filePath));

                // force: the idempotency guard is for ingestion; this command must re-read logs that were already
                // ingested, and must not register the file as ingested either
                $extractionService->extractData($filePath, true, runContext: $runContext);

                return 0;
            });
        } finally {
            $this->laravel->forgetInstance(DataExtractorFactoryInterface::class);
        }

        return $extractor->getObservations();
    }

    /**
     * @param Collection<int, NpcHealthChange> $changes
     */
    private function printChanges(Collection $changes, bool $overwrite): void
    {
        $rows = [];

        $stats = ['create' => 0, 'fill placeholder' => 0, 'overwrite' => 0, 'unchanged' => 0, 'skip' => 0, 'curated' => 0];

        foreach ($changes->sortBy(static fn(NpcHealthChange $change) => $change->npc->id) as $change) {
            $action = $this->getAction($change, $overwrite);
            $stats[$action]++;

            $existing = $change->existingNpcHealth;

            $rows[] = [
                $change->npc->id,
                __($change->npc->name, [], 'en_US'),
                $change->npc->isBoss() ? 'yes' : '',
                $change->observation->getSampleCount(),
                $change->observation->keyLevel,
                number_format($change->scalingFactor, 4),
                number_format($change->observation->getMostObservedMaxHp()),
                $existing === null ? '-' : ($change->isPlaceholder() ? 'placeholder' : number_format($existing->health)),
                $existing->percentage ?? '',
                number_format($change->newHealth),
                $change->getDeltaPercent() === null ? '' : sprintf('%+.1f%%', $change->getDeltaPercent()),
                $action,
            ];
        }

        $this->table(
            ['NPC', 'Name', 'Boss', 'Samples', 'Key', 'Factor', 'Observed max HP', 'Existing', '%', 'New health', 'Δ', 'Action'],
            $rows,
        );

        $this->info(sprintf(
            'create: %d, fill placeholder: %d, overwrite: %d, unchanged: %d, skip (real value, no --overwrite): %d, curated (never written): %d',
            $stats['create'],
            $stats['fill placeholder'],
            $stats['overwrite'],
            $stats['unchanged'],
            $stats['skip'],
            $stats['curated'],
        ));
    }

    private function getAction(NpcHealthChange $change, bool $overwrite): string
    {
        if ($change->curated) {
            return 'curated';
        }

        if ($change->isMissing()) {
            return 'create';
        }

        if ($change->isPlaceholder()) {
            return 'fill placeholder';
        }

        if ($change->isUnchanged()) {
            return 'unchanged';
        }

        return $overwrite ? 'overwrite' : 'skip';
    }
}
