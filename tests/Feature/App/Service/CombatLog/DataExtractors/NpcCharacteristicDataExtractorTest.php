<?php

namespace Tests\Feature\App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Models\Characteristic;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcCharacteristic;
use App\Service\CombatLog\DataExtractors\Logging\NpcCharacteristicDataExtractorLoggingInterface;
use App\Service\CombatLog\DataExtractors\NpcCharacteristicDataExtractor;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('NpcCharacteristicDataExtractor')]
final class NpcCharacteristicDataExtractorTest extends PublicTestCase
{
    private const int    NPC_ID    = 999501;
    private const int    SPELL_ID  = 118; // Polymorph → CHARACTERISTIC_POLYMORPH
    private const string NPC_GUID  = 'Creature-0-2085-2290-22744-999501-00012D4051';
    private const string RAW_EVENT = '8/2/2024 16:24:18.477-4  SPELL_AURA_APPLIED,Player-4184-005B8B04,"TestPlayer",0x512,0x0,Creature-0-2085-2290-22744-999501-00012D4051,"TestNpc",0xa48,0x0,118,"Polymorph",0x40,DEBUFF';

    private NpcCharacteristicDataExtractor $extractor;

    private ExtractedDataResult $result;

    private DataExtractionCurrentDungeon $currentDungeon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(
            NpcCharacteristicDataExtractorLoggingInterface::class,
            fn() => Mockery::mock(NpcCharacteristicDataExtractorLoggingInterface::class)->shouldIgnoreMissing(),
        );

        $this->extractor = new NpcCharacteristicDataExtractor();
        $this->result    = new ExtractedDataResult();

        $dungeon              = Dungeon::first();
        $this->currentDungeon = new DataExtractionCurrentDungeon($dungeon);
    }

    protected function tearDown(): void
    {
        try {
            NpcCharacteristic::where('npc_id', self::NPC_ID)->delete();
            Npc::where('id', self::NPC_ID)->delete();
        } finally {
            parent::tearDown();
        }
    }

    private function createTestNpc(): Npc
    {
        return Npc::create([
            'id'                => self::NPC_ID,
            'classification_id' => 1,
            'npc_type_id'       => 1,
            'npc_class_id'      => 1,
            'display_id'        => null,
            'name'              => 'Test NPC',
            'health_percentage' => null,
            'aggressiveness'    => Npc::AGGRESSIVENESS_AGGRESSIVE,
            'dangerous'         => 0,
            'truesight'         => 0,
        ]);
    }

    private function parsedEvent(): BaseEvent
    {
        return (new CombatLogEntry(self::RAW_EVENT))->parseEvent([], CombatLogVersion::RETAIL_11_0_5);
    }

    #[Test]
    public function extractData_givenMappedSpellAuraAppliedToCreature_createsNpcCharacteristic(): void
    {
        // Arrange
        $this->createTestNpc();

        // Act
        $this->extractor->extractData($this->result, $this->currentDungeon, $this->parsedEvent());

        // Assert
        $this->assertSame(1, $this->result->toArray()['createdNpcCharacteristics']);
        $this->assertDatabaseHas('npc_characteristics', [
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH],
        ]);
    }

    #[Test]
    public function extractData_givenMappedSpellAppliedTwice_createsOnlyOneNpcCharacteristic(): void
    {
        // Arrange
        $this->createTestNpc();
        $parsedEvent = $this->parsedEvent();

        // Act
        $this->extractor->extractData($this->result, $this->currentDungeon, $parsedEvent);
        $this->extractor->extractData($this->result, $this->currentDungeon, $parsedEvent);

        // Assert
        $this->assertSame(1, $this->result->toArray()['createdNpcCharacteristics']);
        $this->assertSame(
            1,
            NpcCharacteristic::where('npc_id', self::NPC_ID)
                ->where('characteristic_id', Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH])
                ->count(),
        );
    }

    #[Test]
    public function extractData_givenNpcAlreadyHasCharacteristic_doesNotCreateDuplicate(): void
    {
        // Arrange
        $this->createTestNpc();
        NpcCharacteristic::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH],
        ]);

        // Act
        $this->extractor->extractData($this->result, $this->currentDungeon, $this->parsedEvent());

        // Assert
        $this->assertSame(0, $this->result->toArray()['createdNpcCharacteristics']);
        $this->assertSame(
            1,
            NpcCharacteristic::where('npc_id', self::NPC_ID)
                ->where('characteristic_id', Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH])
                ->count(),
        );
    }

    #[Test]
    public function extractData_givenUnmappedSpell_doesNotCreateNpcCharacteristic(): void
    {
        // Arrange
        $this->createTestNpc();
        // Spell 9999999 is not in SPELL_CHARACTERISTIC_MAP
        $rawEvent    = '8/2/2024 16:24:18.477-4  SPELL_AURA_APPLIED,Player-4184-005B8B04,"TestPlayer",0x512,0x0,Creature-0-2085-2290-22744-999501-00012D4051,"TestNpc",0xa48,0x0,9999999,"Unknown",0x1,DEBUFF';
        $parsedEvent = (new CombatLogEntry($rawEvent))->parseEvent([], CombatLogVersion::RETAIL_11_0_5);

        // Act
        $this->extractor->extractData($this->result, $this->currentDungeon, $parsedEvent);

        // Assert
        $this->assertSame(0, $this->result->toArray()['createdNpcCharacteristics']);
        $this->assertDatabaseMissing('npc_characteristics', ['npc_id' => self::NPC_ID]);
    }

    #[Test]
    public function extractData_givenMappedSpellButNpcNotInDb_doesNotCreateNpcCharacteristic(): void
    {
        // Arrange — NPC 999501 does not exist in DB

        // Act
        $this->extractor->extractData($this->result, $this->currentDungeon, $this->parsedEvent());

        // Assert
        $this->assertSame(0, $this->result->toArray()['createdNpcCharacteristics']);
        $this->assertDatabaseMissing('npc_characteristics', ['npc_id' => self::NPC_ID]);
    }

    #[Test]
    public function extractData_givenNonCreatureDestination_doesNotCreateNpcCharacteristic(): void
    {
        // Arrange
        $this->createTestNpc();
        // Destination is a Player GUID, not a Creature
        $rawEvent    = '8/2/2024 16:24:18.477-4  SPELL_AURA_APPLIED,Player-4184-005B8B04,"Caster",0x512,0x0,Player-4184-00AABBCC,"Target",0x512,0x0,118,"Polymorph",0x40,DEBUFF';
        $parsedEvent = (new CombatLogEntry($rawEvent))->parseEvent([], CombatLogVersion::RETAIL_11_0_5);

        // Act
        $this->extractor->extractData($this->result, $this->currentDungeon, $parsedEvent);

        // Assert
        $this->assertSame(0, $this->result->toArray()['createdNpcCharacteristics']);
    }
}
