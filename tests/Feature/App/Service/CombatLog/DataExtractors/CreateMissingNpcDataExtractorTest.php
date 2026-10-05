<?php

namespace Tests\Feature\App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataInterface;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcHealth;
use App\Service\CombatLog\DataExtractors\CreateMissingNpcDataExtractor;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('CreateMissingNpcDataExtractor')]
final class CreateMissingNpcDataExtractorTest extends PublicTestCase
{
    private const string COMBAT_LOG_PATH = '/tmp/create-missing-npc-test.log';

    private const int PRE_SEEDED_NPC_ID = 76149;

    private const int UNKNOWN_NPC_ID = 99999901;

    /**
     * %s is filled in with the advanced-data info GUID under test. The rest of the event mirrors a
     * real SPELL_CAST_SUCCESS line - npc id 76149 is a pre-seeded Npc so a Creature-mapped info GUID
     * hits the "already existed" branch and issues no writes.
     */
    private const string RAW_EVENT_TEMPLATE = '8/2/2024 16:24:18.477-4  SPELL_CAST_SUCCESS,Creature-0-4237-1209-2796-76149-0000293D52,"Dread Raven",0xa48,0x80000000,Player-1084-0A5F8492,"Jaxeek-TarrenMill-EU",0x511,0x80000000,999602,"TestSpell",0x20,%s,0000000000000000,436040,436040,3094,437,859,100,413,35241,3,90,100,0,1238.22,1700.90,601,5.7883,287';

    private ExtractedDataResult $result;

    private DataExtractionCurrentDungeon $currentDungeon;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // These tests are write-free only because npc 76149 is pre-seeded, so the Creature-mapped
        // cases hit the "already existed" branch. Fail loudly here if seed drift ever breaks that
        // assumption, rather than silently writing an Npc/NpcDungeon into the shared test DB.
        $this->assertTrue(
            Npc::query()->where('id', self::PRE_SEEDED_NPC_ID)->exists(),
            sprintf('Npc %d must be pre-seeded for these tests to stay write-free', self::PRE_SEEDED_NPC_ID),
        );

        $this->result         = new ExtractedDataResult();
        $this->currentDungeon = new DataExtractionCurrentDungeon(Dungeon::firstOrFail());
    }

    #[Test]
    #[DataProvider('extractData_givenANonCreatureInfoGuid_neverParsesInfoGuid_DataProvider')]
    public function extractData_givenANonCreatureInfoGuid_neverParsesInfoGuid(string $infoGuid): void
    {
        // Arrange
        $extractor    = new CreateMissingNpcDataExtractor();
        $parsedEvent  = $this->parsedEvent(sprintf(self::RAW_EVENT_TEMPLATE, $infoGuid));
        $advancedData = $this->assertAdvancedEvent($parsedEvent);

        // Act
        $extractor->beforeExtract($this->result, self::COMBAT_LOG_PATH);
        $extractor->extractData($this->result, $this->currentDungeon, $parsedEvent);
        $extractor->afterExtract($this->result, self::COMBAT_LOG_PATH);

        // Assert - the raw-prefix gate must bail before touching getInfoGuid(), so the info GUID
        // is never parsed into a Guid instance
        $this->assertFalse($this->infoGuidHasBeenParsed($advancedData));
    }

    /**
     * @return array<string, mixed>
     */
    public static function extractData_givenANonCreatureInfoGuid_neverParsesInfoGuid_DataProvider(): array
    {
        return [
            'Player' => ['Player-1084-0A5F8492'],
            'nil'    => ['0000000000000000'],
        ];
    }

    #[Test]
    #[DataProvider('extractData_givenACreatureMappedInfoGuid_parsesInfoGuid_DataProvider')]
    public function extractData_givenACreatureMappedInfoGuid_parsesInfoGuid(string $infoGuid): void
    {
        // Arrange
        $extractor    = new CreateMissingNpcDataExtractor();
        $parsedEvent  = $this->parsedEvent(sprintf(self::RAW_EVENT_TEMPLATE, $infoGuid));
        $advancedData = $this->assertAdvancedEvent($parsedEvent);

        // Act
        $extractor->beforeExtract($this->result, self::COMBAT_LOG_PATH);
        $extractor->extractData($this->result, $this->currentDungeon, $parsedEvent);
        $extractor->afterExtract($this->result, self::COMBAT_LOG_PATH);

        // Assert - Creature, Pet and Vehicle GUIDs all resolve to the Creature class and must not be
        // silently skipped by a gate that only recognizes the literal "Creature-" prefix
        $this->assertTrue($this->infoGuidHasBeenParsed($advancedData));
    }

    /**
     * @return array<string, mixed>
     */
    public static function extractData_givenACreatureMappedInfoGuid_parsesInfoGuid_DataProvider(): array
    {
        return [
            'Creature' => ['Creature-0-4237-1209-2796-76149-0000293D52'],
            'Pet'      => ['Pet-0-4237-1209-2796-76149-0000293D52'],
            'Vehicle'  => ['Vehicle-0-4237-1209-2796-76149-0000293D52'],
        ];
    }

    #[Test]
    public function extractData_givenAnUnknownCreature_createsTheNpcWithoutAHealthRow(): void
    {
        // Arrange
        $this->assertFalse(Npc::query()->whereKey(self::UNKNOWN_NPC_ID)->exists());

        $extractor   = new CreateMissingNpcDataExtractor();
        $unknownGuid = sprintf('Creature-0-4237-1209-2796-%d-0000293D52', self::UNKNOWN_NPC_ID);
        $rawEvent    = str_replace('Creature-0-4237-1209-2796-76149-0000293D52', $unknownGuid, sprintf(self::RAW_EVENT_TEMPLATE, $unknownGuid));

        try {
            // Act
            $extractor->beforeExtract($this->result, self::COMBAT_LOG_PATH);
            $extractor->extractData($this->result, $this->currentDungeon, $this->parsedEvent($rawEvent));
            $extractor->afterExtract($this->result, self::COMBAT_LOG_PATH);

            // Assert
            $this->assertSame('Dread Raven', Npc::query()->findOrFail(self::UNKNOWN_NPC_ID)->name);
            $this->assertTrue(NpcDungeon::query()
                ->where('npc_id', self::UNKNOWN_NPC_ID)
                ->where('dungeon_id', $this->currentDungeon->dungeon->id)
                ->exists());
            $this->assertFalse(NpcHealth::query()->where('npc_id', self::UNKNOWN_NPC_ID)->exists());
            $this->assertSame(1, $this->result->toArray()['createdNpcs']);
        } finally {
            Npc::query()->whereKey(self::UNKNOWN_NPC_ID)->first()?->delete();
            NpcHealth::query()->where('npc_id', self::UNKNOWN_NPC_ID)->delete();
        }
    }

    #[Test]
    public function extractData_givenAnUnknownCreatureThatWasSummoned_doesNotCreateTheNpc(): void
    {
        // Arrange
        $this->assertFalse(Npc::query()->whereKey(self::UNKNOWN_NPC_ID)->exists());

        $extractor   = new CreateMissingNpcDataExtractor();
        $unknownGuid = $this->unknownCreatureGuid();
        $summonEvent = $this->parsedEvent(sprintf(
            '8/2/2024 16:24:17.477-4  SPELL_SUMMON,Creature-0-4237-1209-2796-76149-0000293D52,"Dread Raven",0xa48,0x0,%s,"Dread Raven",0xa48,0x0,999603,"Summon Raven",0x1',
            $unknownGuid,
        ));

        try {
            // Act
            $extractor->beforeExtract($this->result, self::COMBAT_LOG_PATH);
            $extractor->extractData($this->result, $this->currentDungeon, $summonEvent);
            $extractor->extractData($this->result, $this->currentDungeon, $this->parsedEvent($this->unknownCreatureSourcedEvent($unknownGuid)));
            $extractor->afterExtract($this->result, self::COMBAT_LOG_PATH);

            // Assert
            $this->assertFalse(Npc::query()->whereKey(self::UNKNOWN_NPC_ID)->exists());
            $this->assertSame(0, $this->result->toArray()['createdNpcs']);
        } finally {
            Npc::query()->whereKey(self::UNKNOWN_NPC_ID)->first()?->delete();
        }
    }

    #[Test]
    public function extractData_givenAnUnknownCreatureWithAnOwner_doesNotCreateThePet(): void
    {
        // Arrange
        $this->assertFalse(Npc::query()->whereKey(self::UNKNOWN_NPC_ID)->exists());

        $extractor   = new CreateMissingNpcDataExtractor();
        $unknownGuid = $this->unknownCreatureGuid();
        $rawEvent    = str_replace(
            sprintf('%s,0000000000000000,', $unknownGuid),
            sprintf('%s,Player-1084-0A5F8492,', $unknownGuid),
            $this->unknownCreatureSourcedEvent($unknownGuid),
        );

        try {
            // Act
            $extractor->beforeExtract($this->result, self::COMBAT_LOG_PATH);
            $extractor->extractData($this->result, $this->currentDungeon, $this->parsedEvent($rawEvent));
            $extractor->afterExtract($this->result, self::COMBAT_LOG_PATH);

            // Assert
            $this->assertFalse(Npc::query()->whereKey(self::UNKNOWN_NPC_ID)->exists());
            $this->assertSame(0, $this->result->toArray()['createdNpcs']);
        } finally {
            Npc::query()->whereKey(self::UNKNOWN_NPC_ID)->first()?->delete();
        }
    }

    #[Test]
    public function extractData_givenAnUnknownCreatureThatIsNeitherSourceNorDestination_doesNotCreateTheNpc(): void
    {
        // Arrange - the info GUID names a creature the line does not, so there is no name to give it
        $this->assertFalse(Npc::query()->whereKey(self::UNKNOWN_NPC_ID)->exists());

        $extractor = new CreateMissingNpcDataExtractor();
        $rawEvent  = sprintf(self::RAW_EVENT_TEMPLATE, $this->unknownCreatureGuid());

        try {
            // Act
            $extractor->beforeExtract($this->result, self::COMBAT_LOG_PATH);
            $extractor->extractData($this->result, $this->currentDungeon, $this->parsedEvent($rawEvent));
            $extractor->afterExtract($this->result, self::COMBAT_LOG_PATH);

            // Assert
            $this->assertFalse(Npc::query()->whereKey(self::UNKNOWN_NPC_ID)->exists());
            $this->assertSame(0, $this->result->toArray()['createdNpcs']);
        } finally {
            Npc::query()->whereKey(self::UNKNOWN_NPC_ID)->first()?->delete();
        }
    }

    private function unknownCreatureGuid(): string
    {
        return sprintf('Creature-0-4237-1209-2796-%d-0000293D52', self::UNKNOWN_NPC_ID);
    }

    /**
     * The template event with the unknown creature as both its source and its info GUID.
     */
    private function unknownCreatureSourcedEvent(string $unknownGuid): string
    {
        return str_replace('Creature-0-4237-1209-2796-76149-0000293D52', $unknownGuid, sprintf(self::RAW_EVENT_TEMPLATE, $unknownGuid));
    }

    private function assertAdvancedEvent(BaseEvent $parsedEvent): AdvancedDataInterface
    {
        $this->assertInstanceOf(AdvancedCombatLogEvent::class, $parsedEvent);

        return $parsedEvent->getAdvancedData();
    }

    private function infoGuidHasBeenParsed(AdvancedDataInterface $advancedData): bool
    {
        return new ReflectionProperty($advancedData, 'infoGuid')->getValue($advancedData) !== false;
    }

    private function parsedEvent(string $rawEvent): BaseEvent
    {
        return new CombatLogEntry($rawEvent)->parseEvent([], CombatLogVersion::RETAIL_11_0_5);
    }
}
