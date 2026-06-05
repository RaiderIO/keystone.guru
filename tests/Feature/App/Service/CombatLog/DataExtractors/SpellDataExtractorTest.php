<?php

namespace Tests\Feature\App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\CombatLog\SpellProperty;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell as SpellModel;
use App\Models\Spell\SpellDungeon;
use App\Service\CombatLog\DataExtractors\Logging\SpellDataExtractorLoggingInterface;
use App\Service\CombatLog\DataExtractors\SpellDataExtractor;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SpellDataExtractor')]
final class SpellDataExtractorTest extends PublicTestCase
{
    private const int    NPC_ID          = 999601;
    private const int    SPELL_ID        = 999602;
    private const string COMBAT_LOG_PATH = '/tmp/test.log';

    /** SPELL_AURA_APPLIED, source=NPC, dest=NPC, BUFF → triggers SpellProperty::Aura */
    private const string RAW_BUFF_EVENT = '8/2/2024 16:24:18.477-4  SPELL_AURA_APPLIED,Creature-0-2085-2290-22744-999601-000000000,"TestNpc",0xa48,0x0,Creature-0-2085-2290-22744-999601-000000001,"TestNpc",0xa48,0x0,999602,"TestSpell",0x1,BUFF';

    /** SPELL_INTERRUPT, source=Player, dest=NPC_ID=999601, prefix=Kick/6552, suffix=TestSpell/999602 → triggers SpellProperty::MissInterrupt */
    private const string RAW_INTERRUPT_EVENT = '8/2/2024 16:24:18.477-4  SPELL_INTERRUPT,Player-1084-0B48C032,"TestPlayer",0x512,0x80000000,Creature-0-2085-2290-22744-999601-000000000,"TestNpc",0xa48,0x0,6552,"Kick",0x1,999602,"TestSpell",32';

    private ExtractedDataResult $result;

    private DataExtractionCurrentDungeon $currentDungeon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(
            SpellDataExtractorLoggingInterface::class,
            fn() => Mockery::mock(SpellDataExtractorLoggingInterface::class)->shouldIgnoreMissing(),
        );

        $this->result         = new ExtractedDataResult();
        $dungeon              = Dungeon::first();
        $this->currentDungeon = new DataExtractionCurrentDungeon($dungeon);
    }

    protected function tearDown(): void
    {
        try {
            NpcSpell::where('npc_id', self::NPC_ID)->delete();
            SpellDungeon::where('spell_id', self::SPELL_ID)->delete();
            Npc::where('id', self::NPC_ID)->delete();
            SpellModel::where('id', self::SPELL_ID)->delete();
            CombatLogSpellPropertyObservation::where('spell_id', self::SPELL_ID)->delete();
            CombatLogSpellEvent::where('spell_id', self::SPELL_ID)->delete();
            CombatLogNpcEvent::where('npc_id', self::NPC_ID)->delete();
        } finally {
            parent::tearDown();
        }
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createTestSpell(array $overrides = []): SpellModel
    {
        return SpellModel::create(array_merge([
            'id'              => self::SPELL_ID,
            'game_version_id' => 1,
            'category'        => null,
            'cooldown_group'  => null,
            'dispel_type'     => '',
            'mechanic'        => '',
            'icon_name'       => '',
            'name'            => 'TestSpell',
            'schools_mask'    => 1,
            'miss_types_mask' => 0,
            'aura'            => false,
            'debuff'          => false,
            'cast_time'       => 0,
            'duration'        => 0,
            'selectable'      => false,
            'hidden_on_map'   => false,
            'fetched_data_at' => Carbon::now(),
        ], $overrides));
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
            'aggressiveness'    => Npc::AGGRESSIVENESS_AGGRESSIVE,
            'dangerous'         => 0,
            'truesight'         => 0,
        ]);
    }

    private function makeExtractor(): SpellDataExtractor
    {
        return new SpellDataExtractor();
    }

    private function parsedEvent(string $rawEvent): BaseEvent
    {
        return new CombatLogEntry($rawEvent)->parseEvent([], CombatLogVersion::RETAIL_11_0_5);
    }

    /**
     * Runs the full extract lifecycle: beforeExtract → extractData (one or more events) → afterExtract.
     *
     * @param BaseEvent[] $events
     */
    private function runExtract(SpellDataExtractor $extractor, array $events, string $combatLogPath = self::COMBAT_LOG_PATH): void
    {
        $extractor->beforeExtract($this->result, $combatLogPath);
        foreach ($events as $event) {
            $extractor->extractData($this->result, $this->currentDungeon, $event);
        }
        $extractor->afterExtract($this->result, $combatLogPath);
    }

    #[Test]
    public function afterExtract_givenNewSpellProperty_writesObservationAndUpdatesSpellAndCreatesEvent(): void
    {
        // Arrange
        $this->createTestSpell(['aura' => false]);
        $extractor = $this->makeExtractor();

        // Act
        $this->runExtract($extractor, [$this->parsedEvent(self::RAW_BUFF_EVENT)]);

        // Assert — observation row written to combatlog DB
        $this->assertDatabaseHas('combat_log_spell_property_observations', [
            'spell_id'        => self::SPELL_ID,
            'property'        => SpellProperty::Aura->value,
            'combat_log_path' => self::COMBAT_LOG_PATH,
        ], 'combatlog');

        // Assert — spell.aura updated to true
        $this->assertDatabaseHas('spells', [
            'id'   => self::SPELL_ID,
            'aura' => true,
        ]);

        // Assert — event row written for the property change
        $this->assertDatabaseHas('combat_log_spell_events', [
            'spell_id'   => self::SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyChanged->value,
            'property'   => SpellProperty::Aura->value,
        ], 'combatlog');

        $this->assertSame(1, $this->result->toArray()['updatedSpells']);
    }

    #[Test]
    public function afterExtract_givenAlreadyKnownSpellProperty_writesObservationOnlyNoEvent(): void
    {
        // Arrange
        $this->createTestSpell(['aura' => true]);
        $extractor = $this->makeExtractor();

        // Act
        $this->runExtract($extractor, [$this->parsedEvent(self::RAW_BUFF_EVENT)]);

        // Assert — observation still written (keeps rolling window alive)
        $this->assertDatabaseHas('combat_log_spell_property_observations', [
            'spell_id' => self::SPELL_ID,
            'property' => SpellProperty::Aura->value,
        ], 'combatlog');

        // Assert — no PropertyChanged event since property was already set
        $this->assertDatabaseMissing('combat_log_spell_events', [
            'spell_id'   => self::SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyChanged->value,
        ], 'combatlog');

        $this->assertSame(0, $this->result->toArray()['updatedSpells']);
    }

    #[Test]
    public function afterExtract_givenNewNpcSpellAssignment_createsNpcSpellAndEvent(): void
    {
        // Arrange — spell with 'unknown' category so assignSpellToNpc runs, aura=true so no PropertyChanged noise
        $this->createTestSpell([
            'category' => sprintf('spellcategory.%s', SpellModel::CATEGORY_UNKNOWN),
            'aura'     => true,
        ]);
        $this->createTestNpc();
        $extractor = $this->makeExtractor();

        // Act
        $this->runExtract($extractor, [$this->parsedEvent(self::RAW_BUFF_EVENT)]);

        // Assert — NpcSpell created
        $this->assertDatabaseHas('npc_spells', [
            'npc_id'   => self::NPC_ID,
            'spell_id' => self::SPELL_ID,
        ]);

        // Assert — CombatLogNpcEvent written for the new assignment
        $this->assertDatabaseHas('combat_log_npc_events', [
            'npc_id'      => self::NPC_ID,
            'event_type'  => CombatLogNpcEventType::SpellAssigned->value,
            'model_class' => SpellModel::class,
            'model_id'    => self::SPELL_ID,
        ], 'combatlog');

        $this->assertSame(1, $this->result->toArray()['createdNpcSpells']);
    }

    #[Test]
    public function afterExtract_givenInterruptEvent_writesObservationAndSetsInterruptProperty(): void
    {
        // Arrange — interrupted spell already exists with no miss_types_mask bits set
        $this->createTestSpell(['miss_types_mask' => 0]);
        $extractor = $this->makeExtractor();

        // Act
        $this->runExtract($extractor, [$this->parsedEvent(self::RAW_INTERRUPT_EVENT)]);

        // Assert — observation row written to combatlog DB
        $this->assertDatabaseHas('combat_log_spell_property_observations', [
            'spell_id'        => self::SPELL_ID,
            'property'        => SpellProperty::MissInterrupt->value,
            'combat_log_path' => self::COMBAT_LOG_PATH,
        ], 'combatlog');

        // Assert — miss_types_mask bit 1024 set on the spell
        $this->assertDatabaseHas('spells', [
            'id'              => self::SPELL_ID,
            'miss_types_mask' => 1024,
        ]);

        // Assert — PropertyChanged event written
        $this->assertDatabaseHas('combat_log_spell_events', [
            'spell_id'   => self::SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyChanged->value,
            'property'   => SpellProperty::MissInterrupt->value,
        ], 'combatlog');

        $this->assertSame(1, $this->result->toArray()['updatedSpells']);
    }

    #[Test]
    public function afterExtract_givenInterruptEventForNewSpell_createsSpellThenSetsInterruptProperty(): void
    {
        // Arrange — spell does not exist yet
        $extractor = $this->makeExtractor();

        // Act
        $this->runExtract($extractor, [$this->parsedEvent(self::RAW_INTERRUPT_EVENT)]);

        // Assert — spell was created
        $this->assertDatabaseHas('spells', [
            'id' => self::SPELL_ID,
        ]);

        // Assert — observation row written
        $this->assertDatabaseHas('combat_log_spell_property_observations', [
            'spell_id' => self::SPELL_ID,
            'property' => SpellProperty::MissInterrupt->value,
        ], 'combatlog');

        // Assert — miss_types_mask bit 1024 set
        $this->assertDatabaseHas('spells', [
            'id'              => self::SPELL_ID,
            'miss_types_mask' => 1024,
        ]);

        $this->assertSame(1, $this->result->toArray()['createdSpells']);
        $this->assertSame(1, $this->result->toArray()['updatedSpells']);
    }

    #[Test]
    public function afterExtract_givenMultipleObservationsForSameProperty_writesOneObservationRow(): void
    {
        // Arrange
        $this->createTestSpell(['aura' => false]);
        $extractor   = $this->makeExtractor();
        $parsedEvent = $this->parsedEvent(self::RAW_BUFF_EVENT);

        // Act — same event seen 5 times in one log
        $this->runExtract($extractor, [$parsedEvent, $parsedEvent, $parsedEvent, $parsedEvent, $parsedEvent]);

        // Assert — only one observation row for today
        $this->assertSame(
            1,
            CombatLogSpellPropertyObservation::where('spell_id', self::SPELL_ID)
                ->where('property', SpellProperty::Aura->value)
                ->count(),
        );
    }
}
