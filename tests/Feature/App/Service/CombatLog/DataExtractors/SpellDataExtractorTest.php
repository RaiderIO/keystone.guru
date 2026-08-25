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
use App\Repositories\Swoole\SpellRepositorySwoole;
use App\Service\CombatLog\DataExtractors\Logging\SpellDataExtractorLoggingInterface;
use App\Service\CombatLog\DataExtractors\SpellDataCollectors\SpellCreationCollector;
use App\Service\CombatLog\DataExtractors\SpellDataExtractor;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
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

    /** SPELL_AURA_APPLIED for an unknown spell, prefix school 0x20 (shadow) - hex, exactly as retail logs it */
    private const string RAW_SHADOW_BUFF_EVENT = '8/2/2024 16:24:18.477-4  SPELL_AURA_APPLIED,Creature-0-2085-2290-22744-999601-000000000,"TestNpc",0xa48,0x0,Creature-0-2085-2290-22744-999601-000000001,"TestNpc",0xa48,0x0,999602,"TestSpell",0x20,BUFF';

    /** SPELL_AURA_APPLIED, source=NPC, dest=Player, DEBUFF → triggers SpellProperty::Debuff, the one nullable column */
    private const string RAW_DEBUFF_EVENT = '8/2/2024 16:24:18.477-4  SPELL_AURA_APPLIED,Creature-0-2085-2290-22744-999601-000000000,"TestNpc",0xa48,0x0,Player-1084-0B48C032,"TestPlayer",0x512,0x80000000,999602,"TestSpell",0x1,DEBUFF';

    private ExtractedDataResult $result;

    private DataExtractionCurrentDungeon $currentDungeon;

    #[\Override]
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

    #[\Override]
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
        // A fresh (non-app-bound) repository per extractor - tests create/delete spells between
        // makeExtractor() calls and must not see another test's memoized catalog
        return new SpellDataExtractor(new SpellRepositorySwoole());
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
    public function extractData_givenAnUnknownSpell_createsItWithTheHexPrefixSchool(): void
    {
        // Arrange - the spell does not exist yet, so the collector creates it from the event alone
        $this->createTestNpc();
        $extractor = $this->makeExtractor();

        // Act
        $this->runExtract($extractor, [$this->parsedEvent(self::RAW_SHADOW_BUFF_EVENT)]);

        // Assert - 0x20 is 32, not the 0 a plain (int) cast produces
        $this->assertDatabaseHas('spells', [
            'id'           => self::SPELL_ID,
            'schools_mask' => SpellModel::SCHOOL_SHADOW,
        ]);
    }

    #[Test]
    public function extractData_givenASpellCreatedAfterTheCatalogWasBuilt_findsItInsteadOfCreatingADuplicate(): void
    {
        // Arrange - the catalog is shared across jobs in a long-lived worker (#4058): build it first, then
        // create the spell behind its back, like another worker would
        $this->createTestNpc();
        $extractor = $this->makeExtractor();
        $this->createTestSpell(['schools_mask' => 0]);

        // Act - a blind create here would die on a duplicate primary key
        $this->runExtract($extractor, [$this->parsedEvent(self::RAW_SHADOW_BUFF_EVENT)]);

        // Assert - the existing-spell path ran: the school got repaired, and no SpellCreated event was written
        $this->assertSame(1, SpellModel::where('id', self::SPELL_ID)->count());
        $this->assertDatabaseHas('spells', [
            'id'           => self::SPELL_ID,
            'schools_mask' => SpellModel::SCHOOL_SHADOW,
        ]);
        $this->assertDatabaseMissing('combat_log_spell_events', [
            'spell_id'   => self::SPELL_ID,
            'event_type' => CombatLogSpellEventType::SpellCreated->value,
        ], 'combatlog');
    }

    #[Test]
    public function createSpell_givenAnotherWorkerInsertedTheSameSpellBetweenTheLookupAndTheInsert_fallsBackToTheExistingSpellPathInsteadOfThrowing(): void
    {
        // Arrange - this worker's own findSpell() check already missed (empty catalog, no row yet), simulated
        // here by calling the collector's private createSpell() path directly while another worker's insert has
        // already landed in between (#4151) - a blind create dies on a duplicate primary key
        /** @var SpellDataExtractorLoggingInterface $log */
        $log       = Mockery::mock(SpellDataExtractorLoggingInterface::class)->shouldIgnoreMissing();
        $allSpells = collect();
        $collector = new SpellCreationCollector($allSpells, $log);
        $collector->beforeCollect(self::COMBAT_LOG_PATH);
        $this->createTestSpell(['schools_mask' => 0]);

        // Act
        $createSpell = new ReflectionMethod($collector, 'createSpell');
        $createSpell->invoke($collector, $this->result, self::SPELL_ID, 'TestSpell', SpellModel::SCHOOL_SHADOW);
        $collector->afterCollect($this->result, self::COMBAT_LOG_PATH);

        // Assert - the existing-spell path ran instead of throwing: the school got repaired, still only one row,
        // and no SpellCreated event was written since this worker did not actually create it
        $this->assertSame(1, SpellModel::where('id', self::SPELL_ID)->count());
        $this->assertDatabaseHas('spells', [
            'id'           => self::SPELL_ID,
            'schools_mask' => SpellModel::SCHOOL_SHADOW,
        ]);
        $this->assertDatabaseMissing('combat_log_spell_events', [
            'spell_id'   => self::SPELL_ID,
            'event_type' => CombatLogSpellEventType::SpellCreated->value,
        ], 'combatlog');

        // Assert - counted as an update, not a creation, and the shared catalog now has the winning row so
        // downstream collectors (npc/spell assignments) do not skip this spell for the rest of the run
        $this->assertSame(0, $this->result->toArray()['createdSpells']);
        $this->assertSame(1, $this->result->toArray()['updatedSpells']);
        $this->assertTrue($allSpells->has(self::SPELL_ID));
        $this->assertSame(SpellModel::SCHOOL_SHADOW, $allSpells->get(self::SPELL_ID)->schools_mask);
    }

    #[Test]
    public function extractData_givenAKnownSpellWithoutASchool_repairsItsSchoolsMask(): void
    {
        // Arrange - a spell created from a combat log before #3845, so its school was lost
        $this->createTestSpell(['schools_mask' => 0]);
        $extractor = $this->makeExtractor();

        // Act
        $this->runExtract($extractor, [$this->parsedEvent(self::RAW_SHADOW_BUFF_EVENT)]);

        // Assert
        $this->assertDatabaseHas('spells', [
            'id'           => self::SPELL_ID,
            'schools_mask' => SpellModel::SCHOOL_SHADOW,
        ]);

        // Assert - the repair is auditable from the activity feed, like every other spell mutation here
        $this->assertDatabaseHas('combat_log_spell_events', [
            'spell_id'        => self::SPELL_ID,
            'event_type'      => CombatLogSpellEventType::SchoolRecorded->value,
            'combat_log_path' => self::COMBAT_LOG_PATH,
        ], 'combatlog');
    }

    #[Test]
    public function extractData_givenAnInterruptedSpellWithoutASchool_repairsItsSchoolsMask(): void
    {
        // Arrange - a spell that only ever reappears as the interrupted spell of a SPELL_INTERRUPT
        $this->createTestSpell(['schools_mask' => 0]);
        $this->createTestNpc();
        $extractor = $this->makeExtractor();

        // Act - RAW_INTERRUPT_EVENT carries extraSchool 32, decimal, as retail logs it
        $this->runExtract($extractor, [$this->parsedEvent(self::RAW_INTERRUPT_EVENT)]);

        // Assert
        $this->assertDatabaseHas('spells', [
            'id'           => self::SPELL_ID,
            'schools_mask' => SpellModel::SCHOOL_SHADOW,
        ]);
    }

    #[Test]
    public function extractData_givenAKnownSpellThatAlreadyHasASchool_leavesItAlone(): void
    {
        // Arrange - the database is authoritative once a school is known; a single event must not overwrite it
        $this->createTestSpell(['schools_mask' => SpellModel::SCHOOL_FIRE]);
        $extractor = $this->makeExtractor();

        // Act
        $this->runExtract($extractor, [$this->parsedEvent(self::RAW_SHADOW_BUFF_EVENT)]);

        // Assert
        $this->assertDatabaseHas('spells', [
            'id'           => self::SPELL_ID,
            'schools_mask' => SpellModel::SCHOOL_FIRE,
        ]);
    }

    #[Test]
    public function extractData_givenAKnownSchoollessSpellAndASchoollessEvent_leavesItAlone(): void
    {
        // Arrange - a spell that genuinely has no school must not churn a write on every event
        $this->createTestSpell(['schools_mask' => 0]);

        $log = Mockery::mock(SpellDataExtractorLoggingInterface::class)->shouldIgnoreMissing();

        /** @var Mockery\Expectation $expectation */
        $expectation = $log->shouldReceive('ensureSpellExistsRepairedSchoolsMask');
        $expectation->never();

        $this->app->bind(SpellDataExtractorLoggingInterface::class, fn() => $log);

        $extractor = $this->makeExtractor();

        // Act - RAW_BUFF_EVENT is logged as 0x1, so use an explicitly school-less event
        $schoollessEvent = str_replace('"TestSpell",0x20', '"TestSpell",0x0', self::RAW_SHADOW_BUFF_EVENT);
        $this->runExtract($extractor, [$this->parsedEvent($schoollessEvent)]);

        // Assert
        $this->assertDatabaseHas('spells', [
            'id'           => self::SPELL_ID,
            'schools_mask' => 0,
        ]);
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
    public function afterExtract_givenAnotherWorkerRecordedThePropertyAfterTheCatalogWasBuilt_doesNotEmitASecondEvent(): void
    {
        // Arrange - two ingest processes, each holding its own process-persistent spell catalog (#4058) built while
        // the property was still unset, exactly as concurrent workers do during an ingest burst
        $this->createTestSpell(['aura' => false]);
        $workerA = $this->makeExtractor();
        $workerB = $this->makeExtractor();

        // Act - both observe the same aura, each from its own combat log
        $this->runExtract($workerA, [$this->parsedEvent(self::RAW_BUFF_EVENT)], '/tmp/worker-a.log');
        $this->runExtract($workerB, [$this->parsedEvent(self::RAW_BUFF_EVENT)], '/tmp/worker-b.log');

        // Assert - the property was recorded, but only the worker that actually flipped it wrote an event (#4199)
        $this->assertDatabaseHas('spells', [
            'id'   => self::SPELL_ID,
            'aura' => true,
        ]);
        $this->assertSame(1, CombatLogSpellEvent::on('combatlog')
            ->where('spell_id', self::SPELL_ID)
            ->where('event_type', CombatLogSpellEventType::PropertyChanged->value)
            ->where('property', SpellProperty::Aura->value)
            ->count());
    }

    #[Test]
    public function afterExtract_givenASpellWhoseNullableDebuffColumnIsNull_recordsItAndEmitsExactlyOneEvent(): void
    {
        // Arrange - `debuff` is the one property column that is nullable, and is NULL rather than 0 on most live
        // rows, so the conditional write has to match NULL as well as false
        $this->createTestSpell(['debuff' => null]);
        $workerA = $this->makeExtractor();
        $workerB = $this->makeExtractor();

        // Act
        $this->runExtract($workerA, [$this->parsedEvent(self::RAW_DEBUFF_EVENT)], '/tmp/worker-a.log');
        $this->runExtract($workerB, [$this->parsedEvent(self::RAW_DEBUFF_EVENT)], '/tmp/worker-b.log');

        // Assert
        $this->assertDatabaseHas('spells', [
            'id'     => self::SPELL_ID,
            'debuff' => true,
        ]);
        $this->assertSame(1, CombatLogSpellEvent::on('combatlog')
            ->where('spell_id', self::SPELL_ID)
            ->where('event_type', CombatLogSpellEventType::PropertyChanged->value)
            ->where('property', SpellProperty::Debuff->value)
            ->count());
    }

    #[Test]
    public function afterExtract_givenAnotherWorkerRecordedAMaskPropertyAfterTheCatalogWasBuilt_doesNotEmitASecondEvent(): void
    {
        // Arrange - the miss/counter/bypass properties live in a bitmask column instead of a boolean one, so they
        // take a different conditional-write path than aura/debuff and need their own regression cover
        $this->createTestSpell(['miss_types_mask' => 0]);
        $workerA = $this->makeExtractor();
        $workerB = $this->makeExtractor();

        // Act
        $this->runExtract($workerA, [$this->parsedEvent(self::RAW_INTERRUPT_EVENT)], '/tmp/worker-a.log');
        $this->runExtract($workerB, [$this->parsedEvent(self::RAW_INTERRUPT_EVENT)], '/tmp/worker-b.log');

        // Assert - the bit was set once, and only the worker that set it wrote an event (#4199)
        $this->assertDatabaseHas('spells', [
            'id'              => self::SPELL_ID,
            'miss_types_mask' => SpellModel::MISS_TYPE_INTERRUPT,
        ]);
        $this->assertSame(1, CombatLogSpellEvent::on('combatlog')
            ->where('spell_id', self::SPELL_ID)
            ->where('event_type', CombatLogSpellEventType::PropertyChanged->value)
            ->where('property', SpellProperty::MissInterrupt->value)
            ->count());
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
    public function afterExtract_givenMultipleDistinctPropertiesInOneBatch_writesAllObservationRows(): void
    {
        // Arrange — spell not yet known; both rows land in the same afterExtract upsert batch. Regression
        // check for #4086: the batch is now sorted by its unique key before upserting to keep concurrent
        // jobs' lock order deterministic, which must not drop or corrupt any row in the batch
        $extractor = $this->makeExtractor();

        // Act — two distinct properties (Aura, MissInterrupt) for the same spell in one batch
        $this->runExtract($extractor, [
            $this->parsedEvent(self::RAW_INTERRUPT_EVENT),
            $this->parsedEvent(self::RAW_BUFF_EVENT),
        ]);

        // Assert — both rows present
        $this->assertDatabaseHas('combat_log_spell_property_observations', [
            'spell_id' => self::SPELL_ID,
            'property' => SpellProperty::MissInterrupt->value,
        ], 'combatlog');
        $this->assertDatabaseHas('combat_log_spell_property_observations', [
            'spell_id' => self::SPELL_ID,
            'property' => SpellProperty::Aura->value,
        ], 'combatlog');
        $this->assertSame(
            2,
            CombatLogSpellPropertyObservation::where('spell_id', self::SPELL_ID)->count(),
        );
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
