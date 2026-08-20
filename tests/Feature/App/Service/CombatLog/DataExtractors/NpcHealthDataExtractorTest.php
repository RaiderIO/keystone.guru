<?php

namespace Tests\Feature\App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Service\CombatLog\DataExtractors\NpcHealthDataExtractor;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('NpcHealthDataExtractor')]
final class NpcHealthDataExtractorTest extends PublicTestCase
{
    private const string COMBAT_LOG_PATH = '/tmp/npc-health-test.log';

    private const int NPC_ID = 76149;

    private const string CREATURE_GUID = 'Creature-0-4237-1209-2796-76149-0000293D52';

    private const string NO_OWNER_GUID = '0000000000000000';

    /**
     * %1$s = advanced-data info GUID, %2$s = owner GUID, %3$d = max HP. The rest mirrors a real SPELL_CAST_SUCCESS line.
     */
    private const string RAW_EVENT_TEMPLATE = '8/2/2024 16:24:18.477-4  SPELL_CAST_SUCCESS,Creature-0-4237-1209-2796-76149-0000293D52,"Dread Raven",0xa48,0x80000000,Player-1084-0A5F8492,"Jaxeek-TarrenMill-EU",0x511,0x80000000,999602,"TestSpell",0x20,%1$s,%2$s,436040,%3$d,3094,437,859,100,413,35241,3,90,100,0,1238.22,1700.90,601,5.7883,287';

    private ExtractedDataResult $result;

    private Dungeon $dungeon;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->result  = new ExtractedDataResult();
        $this->dungeon = Dungeon::firstOrFail();
    }

    #[Test]
    public function extractData_givenCreatureInAChallengeMode_recordsObservationWithKeyLevelAndAffixes(): void
    {
        // Arrange
        $extractor      = new NpcHealthDataExtractor();
        $affixGroup     = AffixGroup::with('affixes')->firstOrFail();
        $currentDungeon = new DataExtractionCurrentDungeon($this->dungeon, 7, $affixGroup);
        $parsedEvent    = $this->parsedEvent(self::CREATURE_GUID, self::NO_OWNER_GUID, 1_234_567);

        // Act
        $extractor->beforeExtract($this->result, self::COMBAT_LOG_PATH);
        $extractor->extractData($this->result, $currentDungeon, $parsedEvent);
        $extractor->afterExtract($this->result, self::COMBAT_LOG_PATH);

        // Assert
        $observations = $extractor->getObservations();
        $this->assertCount(1, $observations);
        $observation = $observations->first();
        $this->assertSame(self::NPC_ID, $observation->npcId);
        $this->assertSame($this->dungeon->id, $observation->dungeonId);
        $this->assertSame(7, $observation->keyLevel);
        $this->assertSame($affixGroup->affixes->pluck('key')->toArray(), $observation->affixes);
        $this->assertSame([1_234_567 => 1], $observation->getSamples());
        $this->assertSame(1_234_567, $observation->getMostObservedMaxHp());
    }

    #[Test]
    public function extractData_givenNoKeyLevel_recordsNothing(): void
    {
        // Arrange
        $extractor      = new NpcHealthDataExtractor();
        $currentDungeon = new DataExtractionCurrentDungeon($this->dungeon);
        $parsedEvent    = $this->parsedEvent(self::CREATURE_GUID, self::NO_OWNER_GUID, 1_234_567);

        // Act
        $extractor->extractData($this->result, $currentDungeon, $parsedEvent);

        // Assert
        $this->assertTrue($extractor->getObservations()->isEmpty());
    }

    #[Test]
    #[DataProvider('extractData_givenANonNpcUnit_recordsNothing_DataProvider')]
    public function extractData_givenANonNpcUnit_recordsNothing(string $infoGuid, string $ownerGuid): void
    {
        // Arrange
        $extractor      = new NpcHealthDataExtractor();
        $currentDungeon = new DataExtractionCurrentDungeon($this->dungeon, 2);
        $parsedEvent    = $this->parsedEvent($infoGuid, $ownerGuid, 1_234_567);

        // Act
        $extractor->extractData($this->result, $currentDungeon, $parsedEvent);

        // Assert
        $this->assertTrue($extractor->getObservations()->isEmpty());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function extractData_givenANonNpcUnit_recordsNothing_DataProvider(): array
    {
        return [
            'player'                => ['Player-1084-0A5F8492', self::NO_OWNER_GUID],
            'pet'                   => ['Pet-0-4237-1209-2796-76149-0000293D52', self::NO_OWNER_GUID],
            'vehicle'               => ['Vehicle-0-4237-1209-2796-76149-0000293D52', self::NO_OWNER_GUID],
            'player-owned creature' => [self::CREATURE_GUID, 'Player-1084-0A5F8492'],
            'no info guid'          => [self::NO_OWNER_GUID, self::NO_OWNER_GUID],
        ];
    }

    #[Test]
    public function extractData_givenNonAdvancedEvent_recordsNothing(): void
    {
        // Arrange
        $extractor      = new NpcHealthDataExtractor();
        $currentDungeon = new DataExtractionCurrentDungeon($this->dungeon, 2);
        $parsedEvent    = new CombatLogEntry('8/2/2024 16:24:18.477-4  ZONE_CHANGE,2652,"The Stonevault",23')
            ->parseEvent([], CombatLogVersion::RETAIL_11_0_5);

        // Act
        $extractor->extractData($this->result, $currentDungeon, $parsedEvent);

        // Assert
        $this->assertTrue($extractor->getObservations()->isEmpty());
    }

    #[Test]
    public function getMostObservedMaxHp_givenSeveralMaxHpValues_returnsTheMostFrequentOne(): void
    {
        // Arrange
        $extractor      = new NpcHealthDataExtractor();
        $currentDungeon = new DataExtractionCurrentDungeon($this->dungeon, 2);

        // Act - the buffed value shows up once, the real one twice
        $extractor->extractData($this->result, $currentDungeon, $this->parsedEvent(self::CREATURE_GUID, self::NO_OWNER_GUID, 2_000_000));
        $extractor->extractData($this->result, $currentDungeon, $this->parsedEvent(self::CREATURE_GUID, self::NO_OWNER_GUID, 1_000_000));
        $extractor->extractData($this->result, $currentDungeon, $this->parsedEvent(self::CREATURE_GUID, self::NO_OWNER_GUID, 1_000_000));

        // Assert
        $observation = $extractor->getObservations()->first();
        $this->assertSame(3, $observation->getSampleCount());
        $this->assertSame(1_000_000, $observation->getMostObservedMaxHp());
    }

    #[Test]
    public function getMostObservedMaxHp_givenATie_returnsTheLowestValue(): void
    {
        // Arrange
        $extractor      = new NpcHealthDataExtractor();
        $currentDungeon = new DataExtractionCurrentDungeon($this->dungeon, 2);

        // Act
        $extractor->extractData($this->result, $currentDungeon, $this->parsedEvent(self::CREATURE_GUID, self::NO_OWNER_GUID, 2_000_000));
        $extractor->extractData($this->result, $currentDungeon, $this->parsedEvent(self::CREATURE_GUID, self::NO_OWNER_GUID, 1_000_000));

        // Assert - a buff only ever raises max HP, so the lower value is the unbuffed one
        $this->assertSame(1_000_000, $extractor->getObservations()->first()->getMostObservedMaxHp());
    }

    #[Test]
    public function getObservations_givenASecondFile_accumulatesAcrossFiles(): void
    {
        // Arrange
        $extractor      = new NpcHealthDataExtractor();
        $currentDungeon = new DataExtractionCurrentDungeon($this->dungeon, 2);

        // Act - two segment files of the same run
        $extractor->beforeExtract($this->result, self::COMBAT_LOG_PATH);
        $extractor->extractData($this->result, $currentDungeon, $this->parsedEvent(self::CREATURE_GUID, self::NO_OWNER_GUID, 1_000_000));
        $extractor->afterExtract($this->result, self::COMBAT_LOG_PATH);
        $extractor->beforeExtract($this->result, self::COMBAT_LOG_PATH . '.2');
        $extractor->extractData($this->result, $currentDungeon, $this->parsedEvent(self::CREATURE_GUID, self::NO_OWNER_GUID, 1_000_000));
        $extractor->afterExtract($this->result, self::COMBAT_LOG_PATH . '.2');

        // Assert
        $this->assertCount(1, $extractor->getObservations());
        $this->assertSame(2, $extractor->getObservations()->first()->getSampleCount());
    }

    #[Test]
    public function extractData_givenSameNpcAtTwoKeyLevels_keepsSeparateObservations(): void
    {
        // Arrange
        $extractor = new NpcHealthDataExtractor();

        // Act
        $extractor->extractData($this->result, new DataExtractionCurrentDungeon($this->dungeon, 2), $this->parsedEvent(self::CREATURE_GUID, self::NO_OWNER_GUID, 1_000_000));
        $extractor->extractData($this->result, new DataExtractionCurrentDungeon($this->dungeon, 10), $this->parsedEvent(self::CREATURE_GUID, self::NO_OWNER_GUID, 2_000_000));

        // Assert
        $this->assertSame([2, 10], $extractor->getObservations()->map(static fn($observation) => $observation->keyLevel)->values()->toArray());
    }

    #[Test]
    public function extractData_givenAnyEvent_issuesNoQueries(): void
    {
        // Arrange
        $extractor      = new NpcHealthDataExtractor();
        $currentDungeon = new DataExtractionCurrentDungeon($this->dungeon, 2, AffixGroup::with('affixes')->firstOrFail());
        $parsedEvent    = $this->parsedEvent(self::CREATURE_GUID, self::NO_OWNER_GUID, 1_000_000);
        DB::enableQueryLog();

        // Act
        $extractor->beforeExtract($this->result, self::COMBAT_LOG_PATH);
        $extractor->extractData($this->result, $currentDungeon, $parsedEvent);
        $extractor->afterExtract($this->result, self::COMBAT_LOG_PATH);

        // Assert - observing is in-memory only; writes are the service's job
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertSame([], $queries);
    }

    private function parsedEvent(string $infoGuid, string $ownerGuid, int $maxHp): BaseEvent
    {
        return new CombatLogEntry(sprintf(self::RAW_EVENT_TEMPLATE, $infoGuid, $ownerGuid, $maxHp))
            ->parseEvent([], CombatLogVersion::RETAIL_11_0_5);
    }
}
