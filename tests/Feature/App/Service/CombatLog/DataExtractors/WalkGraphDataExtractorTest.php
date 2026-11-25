<?php

namespace Tests\Feature\App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\WalkGraph\WalkGrid;
use App\Models\CombatLog\CombatLogWalkGraphContribution;
use App\Models\Dungeon;
use App\Repositories\Database\CombatLog\CombatLogWalkGraphContributionRepository;
use App\Service\CombatLog\DataExtractors\WalkGraphDataExtractor;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('WalkGraph')]
final class WalkGraphDataExtractorTest extends PublicTestCase
{
    use CreatesDungeon;

    private const int UI_MAP_ID = 9_990_601;

    private const string COMBAT_LOG_PATH = '/tmp/run_9990601_segment_3.txt';

    private const string PLAYER_GUID = 'Player-1084-0A5F8492';

    private const string CREATURE_GUID = 'Creature-0-4237-1209-2796-76149-0000293D52';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['keystoneguru.walk_graph.cell_size' => 3.0]);
        $this->cleanUpTestData();
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            $this->cleanUpTestData();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function afterExtract_givenPlayerWalkingOnTheDungeonsUiMap_writesTheWalkedEdgesForTheRun(): void
    {
        // Arrange - a player walks 9 yd east in 1.5 s
        $currentDungeon = $this->createCurrentDungeon();
        $extractor      = $this->createExtractor();

        // Act
        $this->extract($extractor, $currentDungeon, [
            $this->createPositionEvent(self::PLAYER_GUID, 0, 1.5, 1.5, self::UI_MAP_ID),
            $this->createPositionEvent(self::PLAYER_GUID, 500, 4.5, 1.5, self::UI_MAP_ID),
            $this->createPositionEvent(self::PLAYER_GUID, 1000, 7.5, 1.5, self::UI_MAP_ID),
            $this->createPositionEvent(self::PLAYER_GUID, 1500, 10.5, 1.5, self::UI_MAP_ID),
        ]);

        // Assert
        $contribution = CombatLogWalkGraphContribution::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->sole();
        $this->assertSame(self::UI_MAP_ID, $contribution->ui_map_id);
        $this->assertSame('run:9990601', $contribution->run_key);
        $this->assertSame(3, $contribution->edge_count);
        $this->assertEqualsCanonicalizing(
            [WalkGrid::edgeKey(0, 0, 1, 0), WalkGrid::edgeKey(1, 0, 2, 0), WalkGrid::edgeKey(2, 0, 3, 0)],
            WalkGrid::decodeEdgeKeys($contribution->edges),
        );
    }

    #[Test]
    public function afterExtract_givenPlayerWalkingOnAnAliasOfTheDungeonsUiMap_writesTheEdgesUnderTheCanonicalUiMap(): void
    {
        // Arrange - the log reports Brackenhide Hollow's alias 2106, the floor stores the canonical 2096
        $currentDungeon = $this->createCurrentDungeon(2096);
        $extractor      = $this->createExtractor();

        // Act
        $this->extract($extractor, $currentDungeon, [
            $this->createPositionEvent(self::PLAYER_GUID, 0, 1.5, 1.5, 2106),
            $this->createPositionEvent(self::PLAYER_GUID, 500, 4.5, 1.5, 2106),
        ]);

        // Assert
        $contribution = CombatLogWalkGraphContribution::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->first();
        $this->assertNotNull($contribution);
        $this->assertSame(2096, $contribution->ui_map_id);
        $this->assertSame([WalkGrid::edgeKey(0, 0, 1, 0)], WalkGrid::decodeEdgeKeys($contribution->edges));
    }

    #[Test]
    public function afterExtract_givenPlayerWalkingOnAnotherUiMap_writesNothing(): void
    {
        // Arrange
        $currentDungeon = $this->createCurrentDungeon();
        $extractor      = $this->createExtractor();

        // Act
        $this->extract($extractor, $currentDungeon, [
            $this->createPositionEvent(self::PLAYER_GUID, 0, 1.5, 1.5, self::UI_MAP_ID + 1),
            $this->createPositionEvent(self::PLAYER_GUID, 500, 4.5, 1.5, self::UI_MAP_ID + 1),
        ]);

        // Assert
        $this->assertFalse(CombatLogWalkGraphContribution::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->exists());
    }

    #[Test]
    public function afterExtract_givenCreatureWalkingOnTheDungeonsUiMap_writesNothing(): void
    {
        // Arrange
        $currentDungeon = $this->createCurrentDungeon();
        $extractor      = $this->createExtractor();

        // Act
        $this->extract($extractor, $currentDungeon, [
            $this->createPositionEvent(self::CREATURE_GUID, 0, 1.5, 1.5, self::UI_MAP_ID),
            $this->createPositionEvent(self::CREATURE_GUID, 500, 4.5, 1.5, self::UI_MAP_ID),
        ]);

        // Assert
        $this->assertFalse(CombatLogWalkGraphContribution::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->exists());
    }

    #[Test]
    public function beforeExtract_givenTheExtractorWasUsedForAnEarlierFile_doesNotCarryItsLastPositionOver(): void
    {
        // Arrange - the earlier file ends with the player at (1.5, 1.5)
        $currentDungeon = $this->createCurrentDungeon();
        $extractor      = $this->createExtractor();
        $this->extract($extractor, $currentDungeon, [
            $this->createPositionEvent(self::PLAYER_GUID, 0, 1.5, 1.5, self::UI_MAP_ID),
        ], '/tmp/run_9990601_segment_2.txt');

        // Act - the next file opens 0.5 s later, 3 yd further
        $this->extract($extractor, $currentDungeon, [
            $this->createPositionEvent(self::PLAYER_GUID, 500, 4.5, 1.5, self::UI_MAP_ID),
        ]);

        // Assert
        $this->assertFalse(CombatLogWalkGraphContribution::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->exists());
    }

    #[Test]
    #[DataProvider('getRunKey_givenCombatLogPath_returnsTheRunKey_DataProvider')]
    public function getRunKey_givenCombatLogPath_returnsTheRunKey(string $combatLogFilePath, string $expectedRunKey): void
    {
        // Arrange & Act
        $runKey = WalkGraphDataExtractor::getRunKey($combatLogFilePath);

        // Assert
        $this->assertSame($expectedRunKey, $runKey);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function getRunKey_givenCombatLogPath_returnsTheRunKey_DataProvider(): array
    {
        return [
            'ingested segment'          => ['/tmp/run_25495473_segment_7.zip', 'run:25495473'],
            'downloaded segment'        => ['/var/www/storage/app/combatlogs/bv/run_25495473_segment_12.txt', 'run:25495473'],
            'reconstructed run'         => ['/var/www/storage/app/combatlogs/bv/full_run_25495473.txt', 'run:25495473'],
            'zipped reconstructed run'  => ['/tmp/full_run_25495473.zip', 'run:25495473'],
            'any other file'            => ['/tmp/WoWCombatLog.txt', sprintf('file:%s', md5('/tmp/WoWCombatLog.txt'))],
            'run id without its prefix' => ['/tmp/my_full_run_25495473.txt', sprintf('file:%s', md5('/tmp/my_full_run_25495473.txt'))],
        ];
    }

    /**
     * @param array<int, BaseEvent> $events
     */
    private function extract(
        WalkGraphDataExtractor       $extractor,
        DataExtractionCurrentDungeon $currentDungeon,
        array                        $events,
        string                       $combatLogFilePath = self::COMBAT_LOG_PATH,
    ): void {
        $result = new ExtractedDataResult();
        $extractor->beforeExtract($result, $combatLogFilePath);
        foreach ($events as $event) {
            $extractor->extractData($result, $currentDungeon, $event);
        }

        $extractor->afterExtract($result, $combatLogFilePath);
    }

    private function createExtractor(): WalkGraphDataExtractor
    {
        return new WalkGraphDataExtractor(new CombatLogWalkGraphContributionRepository());
    }

    private function createCurrentDungeon(int $uiMapId = self::UI_MAP_ID): DataExtractionCurrentDungeon
    {
        $dungeon = $this->createDungeon(withMappingVersion: false);
        $dungeon->floors()->update(['ui_map_id' => $uiMapId]);

        return new DataExtractionCurrentDungeon(Dungeon::findOrFail($dungeon->id));
    }

    /**
     * An advanced-logging line whose info unit is the given unit, at an in-game position.
     */
    private function createPositionEvent(string $infoGuid, int $offsetMs, float $x, float $y, int $uiMapId): BaseEvent
    {
        $timestamp = sprintf('%s-4', Carbon::create(2024, 8, 2, 16, 24, 18)->addMilliseconds($offsetMs)->format('n/j/Y H:i:s.v'));
        $unit      = str_starts_with($infoGuid, 'Player-') ?
            sprintf('%s,"Jaxeek-TarrenMill-EU",0x511,0x80000000', $infoGuid) :
            sprintf('%s,"Dread Raven",0xa48,0x80000000', $infoGuid);

        // The log stores in-game y first and -x second
        $rawEvent = sprintf(
            '%s  SPELL_CAST_SUCCESS,%s,0000000000000000,nil,0x80000000,0x80000000,9990601,"Test Spell",0x1,%s,0000000000000000,436040,436040,3094,437,859,100,413,35241,3,90,100,0,%.2f,%.2f,%d,5.7883,287',
            $timestamp,
            $unit,
            $infoGuid,
            $y,
            -$x,
            $uiMapId,
        );

        return new CombatLogEntry($rawEvent)->parseEvent([], CombatLogVersion::RETAIL_11_0_5);
    }

    private function cleanUpTestData(): void
    {
        CombatLogWalkGraphContribution::query()->where('combat_log_path', 'like', '/tmp/run_9990601_segment_%')->delete();
    }
}
