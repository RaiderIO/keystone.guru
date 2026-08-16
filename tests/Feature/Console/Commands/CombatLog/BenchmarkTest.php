<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use App\Logging\StructuredLogging;
use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcDungeon;
use App\Service\CombatLog\DataExtractors\CreateMissingNpcDataExtractor;
use App\Service\CombatLog\DataExtractors\DataExtractorFactory;
use App\Service\CombatLog\DataExtractors\DataExtractorFactoryInterface;
use App\Service\CombatLog\DataExtractors\FloorDataExtractor;
use App\Service\CombatLog\DataExtractors\ImmunityBypassDataExtractor;
use App\Service\CombatLog\DataExtractors\NpcCharacteristicDataExtractor;
use App\Service\CombatLog\DataExtractors\NpcUpdateDataExtractor;
use App\Service\CombatLog\DataExtractors\Profiling\ProfilingDataExtractor;
use App\Service\CombatLog\DataExtractors\SpellCounterDataExtractor;
use App\Service\CombatLog\DataExtractors\SpellDataExtractor;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('CombatLog')]
final class BenchmarkTest extends PublicTestCase
{
    /** @var string[] Files created during a test, removed in tearDown */
    private array $temporaryFiles = [];

    #[\Override]
    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $temporaryFile) {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
        $this->temporaryFiles = [];

        // The command disables structured logging for its measurement - never leak that into later tests
        StructuredLogging::enable();

        parent::tearDown();
    }

    #[Test]
    public function createExtractors_givenDefaultContainerBinding_returnsUndecoratedExtractors(): void
    {
        // Arrange
        $factory = app(DataExtractorFactoryInterface::class);

        // Act
        $extractors = $factory->createExtractors();

        // Assert - the "zero cost when off" guarantee: the production object graph must never contain
        // profiling decorators
        $this->assertInstanceOf(DataExtractorFactory::class, $factory);
        foreach ($extractors as $extractor) {
            $this->assertNotInstanceOf(ProfilingDataExtractor::class, $extractor);
        }
    }

    #[Test]
    public function createExtractors_givenDefaultFactory_returnsSevenExtractorsInCurrentOrder(): void
    {
        // Arrange
        $factory = app(DataExtractorFactoryInterface::class);

        // Act
        $extractorClasses = $factory->createExtractors()
            ->map(static fn(object $extractor) => $extractor::class)
            ->toArray();

        // Assert - the order is load-bearing (e.g. CreateMissingNpcDataExtractor must run before extractors
        // that expect the NPC to exist)
        $this->assertSame([
            CreateMissingNpcDataExtractor::class,
            NpcUpdateDataExtractor::class,
            FloorDataExtractor::class,
            SpellDataExtractor::class,
            NpcCharacteristicDataExtractor::class,
            SpellCounterDataExtractor::class,
            ImmunityBypassDataExtractor::class,
        ], $extractorClasses);
    }

    #[Test]
    #[SlowTest]
    public function handle_givenNoPrimingRuns_returnsSteadyStateNotReachedAndExitCodeOne(): void
    {
        // Arrange
        $npcId    = $this->uniqueNpcId();
        $logPath  = $this->createSyntheticCombatLog($npcId);
        $jsonPath = $this->temporaryFilePath('json');

        try {
            // Act - without priming, the first measured run creates the NPC and the second finds it, so the
            // two runs did different work
            $this->artisan('combatlog:benchmark', [
                '--file'                 => $logPath,
                '--runs'                 => 2,
                '--priming-runs'         => 0,
                '--json'                 => $jsonPath,
                '--i-know-what-im-doing' => true,
            ])
                ->expectsOutputToContain('STEADY STATE NOT REACHED')
                ->assertExitCode(1);

            // Assert
            $result = json_decode(File::get($jsonPath), true);
            $this->assertFalse($result['phaseA']['steadyState']);
        } finally {
            $this->cleanupExtractionResults($npcId, $logPath);
        }
    }

    #[Test]
    #[SlowTest]
    public function handle_givenProfileOption_reportsEveryExtractorWithCallsAndPercentagesSummingToHundred(): void
    {
        // Arrange
        $npcId    = $this->uniqueNpcId();
        $logPath  = $this->createSyntheticCombatLog($npcId);
        $jsonPath = $this->temporaryFilePath('json');

        try {
            // Act
            $this->artisan('combatlog:benchmark', [
                '--file'                 => $logPath,
                '--runs'                 => 1,
                '--priming-runs'         => 1,
                '--profile'              => true,
                '--json'                 => $jsonPath,
                '--i-know-what-im-doing' => true,
            ])->assertExitCode(0);

            // Assert - every extractor must actually have been exercised; a synthetic log without the
            // advanced-logging header or CHALLENGE_MODE_START would make every extractor assertion pass
            // vacuously against zeros
            $result          = json_decode(File::get($jsonPath), true);
            $reportedClasses = array_column($result['phaseB']['extractors'], 'class');

            $this->assertEqualsCanonicalizing([
                CreateMissingNpcDataExtractor::class,
                NpcUpdateDataExtractor::class,
                FloorDataExtractor::class,
                SpellDataExtractor::class,
                NpcCharacteristicDataExtractor::class,
                SpellCounterDataExtractor::class,
                ImmunityBypassDataExtractor::class,
            ], $reportedClasses);
            foreach ($result['phaseB']['extractors'] as $extractor) {
                // Specifically extractData: beforeExtract/afterExtract fire once per file no matter what the
                // log contains, so only extractData's call count proves events actually reached the extractors
                $this->assertGreaterThan(
                    0,
                    $extractor['lifecycle']['extractData']['calls'],
                    sprintf('%s never received an event - the synthetic log did not drive the extractor loop', $extractor['class']),
                );
            }
            $this->assertEqualsWithDelta(100.0, array_sum(array_column($result['phaseB']['extractors'], 'pct')), 0.1);
        } finally {
            $this->cleanupExtractionResults($npcId, $logPath);
        }
    }

    #[Test]
    #[SlowTest]
    public function handle_givenBaselineWithMismatchedFingerprint_returnsExitCodeOneWithoutDelta(): void
    {
        // Arrange
        $npcId        = $this->uniqueNpcId();
        $logPath      = $this->createSyntheticCombatLog($npcId);
        $jsonPath     = $this->temporaryFilePath('json');
        $baselinePath = $this->temporaryFilePath('json');

        try {
            $this->artisan('combatlog:benchmark', [
                '--file'                 => $logPath,
                '--runs'                 => 1,
                '--json'                 => $jsonPath,
                '--i-know-what-im-doing' => true,
            ])->assertExitCode(0);

            $baseline = json_decode(File::get($jsonPath), true);

            $baseline['fingerprint']['phpVersion'] = '0.0.0';
            File::put($baselinePath, json_encode($baseline));

            // Act + Assert
            $this->artisan('combatlog:benchmark', [
                '--file'                 => $logPath,
                '--runs'                 => 1,
                '--baseline'             => $baselinePath,
                '--i-know-what-im-doing' => true,
            ])
                ->expectsOutputToContain('Fingerprint mismatch')
                ->doesntExpectOutputToContain('Delta vs baseline')
                ->assertExitCode(1);
        } finally {
            $this->cleanupExtractionResults($npcId, $logPath);
        }
    }

    #[Test]
    #[SlowTest]
    public function handle_givenSingleRunBaseline_refusesDeltaAsEvidenceAndReturnsExitCodeOne(): void
    {
        // Arrange
        $npcId        = $this->uniqueNpcId();
        $logPath      = $this->createSyntheticCombatLog($npcId);
        $baselinePath = $this->temporaryFilePath('json');

        try {
            $this->artisan('combatlog:benchmark', [
                '--file'                 => $logPath,
                '--runs'                 => 1,
                '--json'                 => $baselinePath,
                '--i-know-what-im-doing' => true,
            ])->assertExitCode(0);

            // Act + Assert - a single measured run makes the steady-state and variance guards inert, so the
            // delta must not be presented as evidence
            $this->artisan('combatlog:benchmark', [
                '--file'                 => $logPath,
                '--runs'                 => 1,
                '--baseline'             => $baselinePath,
                '--i-know-what-im-doing' => true,
            ])
                ->expectsOutputToContain('THIS DELTA IS NOT EVIDENCE')
                ->assertExitCode(1);
        } finally {
            $this->cleanupExtractionResults($npcId, $logPath);
        }
    }

    #[Test]
    public function handle_givenDirectoryAsFile_returnsExitCodeOne(): void
    {
        // Arrange + Act + Assert - directories can contain expected-output fixtures (tests/CombatLogs/
        // *_events.txt) that would be fed straight to the parser
        $this->artisan('combatlog:benchmark', [
            '--file'                 => base_path('tests/CombatLogs'),
            '--i-know-what-im-doing' => true,
        ])
            ->expectsOutputToContain('refusing to benchmark')
            ->assertExitCode(1);
    }

    #[Test]
    public function handle_givenMissingAcknowledgementFlag_returnsExitCodeOne(): void
    {
        // Arrange + Act + Assert
        $this->artisan('combatlog:benchmark', [
            '--file' => base_path('tests/CombatLogs/WoWCombatLog-050923_172619_7_freehold.zip'),
        ])
            ->expectsOutputToContain('--i-know-what-im-doing')
            ->assertExitCode(1);
    }

    /**
     * A creature id that cannot exist in the seeded database, unique per test run so a failed cleanup in one
     * run cannot poison the next.
     */
    private function uniqueNpcId(): int
    {
        return 900000000 + random_int(1, 99999999);
    }

    private function temporaryFilePath(string $extension): string
    {
        $path = storage_path(sprintf('app/test-benchmark-%s.%s', uniqid(), $extension));

        $this->temporaryFiles[] = $path;

        return $path;
    }

    /**
     * A minimal but real combat log: the COMBAT_LOG_VERSION header with ADVANCED_LOG_ENABLED,1 and a
     * CHALLENGE_MODE_START are mandatory - without them the extraction service returns before any extractor
     * runs and every assertion passes vacuously against zeros. The SWING_DAMAGE lines carry an advanced-info
     * Creature guid for an NPC that does not exist, which drives CreateMissingNpcDataExtractor to create it
     * on the first pass.
     */
    private function createSyntheticCombatLog(int $npcId): string
    {
        $lines = [
            '5/9 21:32:30.034  COMBAT_LOG_VERSION,20,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,10.1.0,PROJECT_ID,1',
            '5/9 21:32:30.034  ZONE_CHANGE,1754,"Freehold",23',
            '5/9 21:34:59.959  CHALLENGE_MODE_START,"Freehold",1754,245,7,[9,124]',
        ];

        for ($lineIndex = 0; $lineIndex < 37; $lineIndex++) {
            $lines[] = sprintf(
                '5/9 21:35:%02d.%03d  SWING_DAMAGE,Creature-0-3878-1754-5338-%d-0001DB1EF3,"Benchmark Test Npc",0xa48,0x0,Player-3684-0DE448BF,"Bunten-Mal\'Ganis",0x511,0x8,Creature-0-3878-1754-5338-%d-0001DB1EF3,0000000000000000,2094042,2323788,0,0,5043,0,1,0,0,0,-1608.11,-993.17,936,3.2247,71,63183,137687,-1,1,0,0,0,nil,nil,nil',
                10 + intdiv($lineIndex, 10),
                ($lineIndex % 10) * 100,
                $npcId,
                $npcId,
            );
        }

        $path = $this->temporaryFilePath('txt');
        File::put($path, implode(PHP_EOL, $lines) . PHP_EOL);

        return $path;
    }

    /**
     * The extraction pipeline performs real writes (that is the point of the benchmark) - remove everything
     * a synthetic-log run can have created.
     */
    private function cleanupExtractionResults(int $npcId, string $logPath): void
    {
        NpcDungeon::query()->where('npc_id', $npcId)->delete();
        Npc::query()->where('id', $npcId)->delete();
        new Npc()->flushCache();

        CombatLogSpellPropertyObservation::query()->where('combat_log_path', $logPath)->delete();
    }
}
