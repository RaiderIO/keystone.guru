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
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use App\Service\CombatLog\DataExtractors\Logging\SpellCounterDataExtractorLoggingInterface;
use App\Service\CombatLog\DataExtractors\SpellCounterDataExtractor;
use App\Service\CombatLog\DataExtractors\SpellCounters\ShadowmeldSpellCounterDefinition;
use App\Service\CombatLog\DataExtractors\SpellCounters\VanishSpellCounterDefinition;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Carbon;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('SpellCounterDataExtractor')]
final class SpellCounterDataExtractorTest extends PublicTestCase
{
    private const string COMBAT_LOG_PATH = '/tmp/spell-counter-test.log';

    private const string PLAYER_GUID       = 'Player-1084-0A5F8492';
    private const string OTHER_PLAYER_GUID = 'Player-1084-0B123456';
    private const string CREATURE_GUID     = 'Creature-0-4237-1209-2796-76149-0000293D52';

    /** @var int The npc id embedded in CREATURE_GUID - Dread Raven, present in the seeded Skyreach mapping */
    private const int CREATURE_NPC_ID = 76149;

    /** @var int The first test spell id - all test spells live in [TEST_SPELL_ID_FIRST, TEST_SPELL_ID_LAST] */
    private const int TEST_SPELL_ID_FIRST = 9990001;

    private const int TEST_SPELL_ID_LAST = 9990020;

    private SpellCounterDataExtractor $extractor;

    private ExtractedDataResult $result;

    private DataExtractionCurrentDungeon $currentDungeon;

    private MockInterface $log;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $log = Mockery::mock(SpellCounterDataExtractorLoggingInterface::class);
        $log->shouldIgnoreMissing();
        $this->log = $log;
        $this->app->bind(SpellCounterDataExtractorLoggingInterface::class, fn() => $this->log);

        $this->cleanUpTestData();

        $this->extractor = new SpellCounterDataExtractor();
        $this->result    = new ExtractedDataResult();

        $dungeon              = Dungeon::first();
        $this->currentDungeon = new DataExtractionCurrentDungeon($dungeon);
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
    public function extractData_givenTargetingDebuffDroppedJustBeforeVanish_setsCounterOnCastSpell(): void
    {
        // Arrange - High Sage Viryx style: the NPC casts Lens Flare (9990001), the targeting debuff (9990002) is
        // applied with a nil source guid, and it is dropped 1ms *before* the Vanish cast success line
        $castSpellId   = 9990001;
        $debuffSpellId = 9990002;
        $this->createTestSpell($castSpellId);
        $this->createTestSpell($debuffSpellId, 12000);
        $this->expectDetectionLoggedForSignature('A', $castSpellId, SpellProperty::CounterVanish);

        // Act
        $this->runExtract([
            $this->npcCastStart(0, $castSpellId, 'Lens Flare'),
            $this->debuffApplied(0, $debuffSpellId, 'Lens Flare', null),
            $this->debuffRemoved(1999, $debuffSpellId, 'Lens Flare', null),
            $this->playerCastSuccess(2000, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
        ]);

        // Assert - the fact attaches to the cast spell, not to the targeting debuff
        $this->assertSame(1, $this->result->toArray()['addedSpellCounters']);
        $this->assertCounterRecorded($castSpellId, Spell::COUNTER_VANISH, SpellProperty::CounterVanish);
        $this->assertNoCounterRecorded($debuffSpellId);

        // Assert - the countered spell is assigned to the casting NPC (cast-only spells with a nil destination never
        // pass SpellDataExtractor's assignment gate, so the counter detection is the only proof the NPC casts it)
        $this->assertTrue(NpcSpell::where('npc_id', self::CREATURE_NPC_ID)->where('spell_id', $castSpellId)->exists());
        $this->assertTrue(SpellDungeon::where('spell_id', $castSpellId)->where('dungeon_id', $this->currentDungeon->dungeon->id)->exists());
        $this->assertSame(1, CombatLogNpcEvent::where('npc_id', self::CREATURE_NPC_ID)
            ->where('event_type', CombatLogNpcEventType::SpellAssigned)
            ->where('model_class', Spell::class)
            ->where('model_id', $castSpellId)
            ->count());
    }

    #[Test]
    public function extractData_givenTargetingDebuffDroppedJustAfterVanish_setsCounterOnCastSpell(): void
    {
        // Arrange - same signature, but the removal line comes *after* the counter cast
        $castSpellId   = 9990003;
        $debuffSpellId = 9990004;
        $this->createTestSpell($castSpellId);
        $this->createTestSpell($debuffSpellId, 12000);

        // Act
        $this->runExtract([
            $this->npcCastStart(0, $castSpellId, 'Lens Flare'),
            $this->debuffApplied(0, $debuffSpellId, 'Lens Flare', null),
            $this->playerCastSuccess(2000, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->debuffRemoved(2080, $debuffSpellId, 'Lens Flare', null),
        ]);

        // Assert
        $this->assertSame(1, $this->result->toArray()['addedSpellCounters']);
        $this->assertCounterRecorded($castSpellId, Spell::COUNTER_VANISH, SpellProperty::CounterVanish);
        $this->assertNoCounterRecorded($debuffSpellId);
    }

    #[Test]
    public function extractData_givenChannelDebuffRemovedAtVanish_setsCounterOnDebuffSpell(): void
    {
        // Arrange - Solar Construct style: an NPC sourced channel debuff broken 1.7s into a 6s channel
        $channelSpellId = 9990005;
        $this->createTestSpell($channelSpellId, 6000);
        $this->expectDetectionLoggedForSignature('B', $channelSpellId, SpellProperty::CounterVanish);

        // Act
        $this->runExtract([
            $this->npcCastSuccess(0, $channelSpellId, 'Solar Flame'),
            $this->debuffApplied(0, $channelSpellId, 'Solar Flame', self::CREATURE_GUID),
            $this->playerCastSuccess(1700, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->debuffRemoved(1700, $channelSpellId, 'Solar Flame', self::CREATURE_GUID),
        ]);

        // Assert
        $this->assertSame(1, $this->result->toArray()['addedSpellCounters']);
        $this->assertCounterRecorded($channelSpellId, Spell::COUNTER_VANISH, SpellProperty::CounterVanish);
    }

    #[Test]
    public function extractData_givenShadowmeldTrigger_setsShadowmeldCounter(): void
    {
        // Arrange
        $channelSpellId = 9990006;
        $this->createTestSpell($channelSpellId, 6000);

        // Act
        $this->runExtract([
            $this->npcCastSuccess(0, $channelSpellId, 'Solar Flame'),
            $this->debuffApplied(0, $channelSpellId, 'Solar Flame', self::CREATURE_GUID),
            $this->playerCastSuccess(1700, ShadowmeldSpellCounterDefinition::SPELL_ID_SHADOWMELD, 'Shadowmeld'),
            $this->debuffRemoved(1700, $channelSpellId, 'Solar Flame', self::CREATURE_GUID),
        ]);

        // Assert
        $this->assertSame(1, $this->result->toArray()['addedSpellCounters']);
        $this->assertCounterRecorded($channelSpellId, Spell::COUNTER_SHADOWMELD, SpellProperty::CounterShadowmeld);
    }

    #[Test]
    public function extractData_givenAbandonedCastSupersededAfterVanish_setsCounterOnAbandonedCastSpell(): void
    {
        // Arrange - Dread Raven style: the cast never reaches CAST_SUCCESS, the caster simply starts over
        $castSpellId = 9990007;
        $this->createTestSpell($castSpellId);
        $this->expectDetectionLoggedForSignature('C', $castSpellId, SpellProperty::CounterVanish);

        // Act
        $this->runExtract([
            $this->npcCastStart(0, $castSpellId, 'Dread Wind'),
            $this->playerCastSuccess(1000, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->npcCastStart(1600, $castSpellId, 'Dread Wind'),
        ]);

        // Assert
        $this->assertSame(1, $this->result->toArray()['addedSpellCounters']);
        $this->assertCounterRecorded($castSpellId, Spell::COUNTER_VANISH, SpellProperty::CounterVanish);
    }

    #[Test]
    public function extractData_givenCastSucceedsAfterVanish_doesNotSetCounter(): void
    {
        // Arrange - the cast resolved, so the counter did not stop it
        $castSpellId = 9990008;
        $this->createTestSpell($castSpellId);

        // Act
        $this->runExtract([
            $this->npcCastStart(0, $castSpellId, 'Dread Wind'),
            $this->playerCastSuccess(1000, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->npcCastSuccess(1550, $castSpellId, 'Dread Wind'),
            $this->npcCastStart(2000, $castSpellId, 'Dread Wind'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellCounters']);
        $this->assertNoCounterRecorded($castSpellId);
    }

    #[Test]
    public function extractData_givenCasterWasInterrupted_doesNotSetCounter(): void
    {
        // Arrange
        $castSpellId = 9990009;
        $this->createTestSpell($castSpellId);

        // Act
        $this->runExtract([
            $this->npcCastStart(0, $castSpellId, 'Dread Wind'),
            $this->playerCastSuccess(1000, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->interrupt(1400, $castSpellId, 'Dread Wind'),
            $this->npcCastStart(1600, $castSpellId, 'Dread Wind'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellCounters']);
        $this->assertNoCounterRecorded($castSpellId);
    }

    #[Test]
    public function extractData_givenCasterDied_doesNotSetCounter(): void
    {
        // Arrange
        $castSpellId = 9990010;
        $this->createTestSpell($castSpellId);

        // Act
        $this->runExtract([
            $this->npcCastStart(0, $castSpellId, 'Dread Wind'),
            $this->playerCastSuccess(1000, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->unitDied(1400),
            $this->npcCastStart(1600, $castSpellId, 'Dread Wind'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellCounters']);
        $this->assertNoCounterRecorded($castSpellId);
    }

    #[Test]
    public function extractData_givenCasterWasStunnedMidCast_doesNotSetCounter(): void
    {
        // Arrange - a loss-of-control debuff on the caster (Kidney Shot carries spellmechanic.stunned in the seeded
        // spells table) is a possible explanation for the abandoned cast
        $castSpellId = 9990011;
        $this->createTestSpell($castSpellId);

        // Act
        $this->runExtract([
            $this->npcCastStart(0, $castSpellId, 'Dread Wind'),
            $this->playerCastSuccess(1000, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->debuffApplied(1200, 408, 'Kidney Shot', self::PLAYER_GUID, self::CREATURE_GUID),
            $this->npcCastStart(1600, $castSpellId, 'Dread Wind'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellCounters']);
        $this->assertNoCounterRecorded($castSpellId);
    }

    #[Test]
    public function extractData_givenCasterHadOnlyDamageDebuffsMidCast_setsCounterOnAbandonedCastSpell(): void
    {
        // Arrange - trash NPCs are debuffed by player damage kits near-constantly (Judgment, bleeds, ...); only
        // loss-of-control mechanics may veto signature C, so a mechanic-less debuff must not block detection
        $castSpellId   = 9990016;
        $damageSpellId = 9990017;
        $this->createTestSpell($castSpellId);
        $this->createTestSpell($damageSpellId);
        $this->expectDetectionLoggedForSignature('C', $castSpellId, SpellProperty::CounterVanish);

        // Act
        $this->runExtract([
            $this->npcCastStart(0, $castSpellId, 'Dread Wind'),
            $this->debuffApplied(500, $damageSpellId, 'Test Judgment', self::PLAYER_GUID, self::CREATURE_GUID),
            $this->playerCastSuccess(1000, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->debuffApplied(1200, $damageSpellId, 'Test Judgment', self::PLAYER_GUID, self::CREATURE_GUID),
            $this->npcCastStart(1600, $castSpellId, 'Dread Wind'),
        ]);

        // Assert
        $this->assertSame(1, $this->result->toArray()['addedSpellCounters']);
        $this->assertCounterRecorded($castSpellId, Spell::COUNTER_VANISH, SpellProperty::CounterVanish);
    }

    #[Test]
    public function extractData_givenCastSupersededLongAfterVanish_doesNotSetCounter(): void
    {
        // Arrange - the new cast starts well outside C_SUPERSEDE_WINDOW_MS
        $castSpellId = 9990012;
        $this->createTestSpell($castSpellId);

        // Act
        $this->runExtract([
            $this->npcCastStart(0, $castSpellId, 'Dread Wind'),
            $this->playerCastSuccess(1000, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->npcCastStart(5000, $castSpellId, 'Dread Wind'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellCounters']);
        $this->assertNoCounterRecorded($castSpellId);
    }

    #[Test]
    public function extractData_givenDebuffRemovedOutsideEpsilon_doesNotSetCounter(): void
    {
        // Arrange - the debuff dropped 300ms after the counter: a real server-tick match is 0-1ms, and the one
        // observed bulk-validation false positive (a churning AoE debuff) sat at 392ms, so this must NOT match
        $channelSpellId = 9990013;
        $this->createTestSpell($channelSpellId);

        // Act
        $this->runExtract([
            $this->npcCastSuccess(0, $channelSpellId, 'Solar Flame'),
            $this->debuffApplied(0, $channelSpellId, 'Solar Flame', self::CREATURE_GUID),
            $this->playerCastSuccess(1000, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->debuffRemoved(1300, $channelSpellId, 'Solar Flame', self::CREATURE_GUID),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellCounters']);
        $this->assertNoCounterRecorded($channelSpellId);
    }

    #[Test]
    public function extractData_givenDebuffLastedItsFullDuration_doesNotSetCounter(): void
    {
        // Arrange - the debuff's own duration is what decides natural expiry, not the duration of the cast the
        // detection would be attributed to (which deliberately has none here)
        $castSpellId   = 9990014;
        $debuffSpellId = 9990015;
        $this->createTestSpell($castSpellId);
        $this->createTestSpell($debuffSpellId, 2000);

        // Act
        $this->runExtract([
            $this->npcCastStart(0, $castSpellId, 'Lens Flare'),
            $this->debuffApplied(0, $debuffSpellId, 'Lens Flare', null),
            $this->playerCastSuccess(2000, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->debuffRemoved(2000, $debuffSpellId, 'Lens Flare', null),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellCounters']);
        $this->assertNoCounterRecorded($castSpellId);
        $this->assertNoCounterRecorded($debuffSpellId);
    }

    #[Test]
    public function extractData_givenCounteredSpellWithPlayerCategory_doesNotAssignSpellToNpc(): void
    {
        // Arrange - a categorized player spell must never gain a permanent NpcSpell row, even when a counter is
        // (mis)attributed to it; the counter bit itself is still recorded and self-corrects via staleness
        $channelSpellId = 9990019;
        $this->createTestSpell($channelSpellId, 6000, 'spellcategory.rogue');

        // Act
        $this->runExtract([
            $this->npcCastSuccess(0, $channelSpellId, 'Solar Flame'),
            $this->debuffApplied(0, $channelSpellId, 'Solar Flame', self::CREATURE_GUID),
            $this->playerCastSuccess(1700, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->debuffRemoved(1700, $channelSpellId, 'Solar Flame', self::CREATURE_GUID),
        ]);

        // Assert
        $this->assertCounterRecorded($channelSpellId, Spell::COUNTER_VANISH, SpellProperty::CounterVanish);
        $this->assertFalse(NpcSpell::where('npc_id', self::CREATURE_NPC_ID)->where('spell_id', $channelSpellId)->exists());
    }

    #[Test]
    public function extractData_givenRefreshedDebuffRemovedAtVanish_setsCounterOnDebuffSpell(): void
    {
        // Arrange - a 6s debuff is refreshed at 5s, then countered at 8s: measured from the original application it
        // has outlived its duration, but the refresh restarted the clock, so the natural-expiry guard must not veto
        $channelSpellId = 9990018;
        $this->createTestSpell($channelSpellId, 6000);
        $this->expectDetectionLoggedForSignature('B', $channelSpellId, SpellProperty::CounterVanish);

        // Act
        $this->runExtract([
            $this->npcCastSuccess(0, $channelSpellId, 'Solar Flame'),
            $this->debuffApplied(0, $channelSpellId, 'Solar Flame', self::CREATURE_GUID),
            $this->debuffRefreshed(5000, $channelSpellId, 'Solar Flame', self::CREATURE_GUID),
            $this->playerCastSuccess(8000, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->debuffRemoved(8000, $channelSpellId, 'Solar Flame', self::CREATURE_GUID),
        ]);

        // Assert
        $this->assertSame(1, $this->result->toArray()['addedSpellCounters']);
        $this->assertCounterRecorded($channelSpellId, Spell::COUNTER_VANISH, SpellProperty::CounterVanish);
    }

    #[Test]
    public function extractData_givenSameCounterDetectedTwice_writesOneObservationAndOneEvent(): void
    {
        // Arrange
        $channelSpellId = 9990016;
        $this->createTestSpell($channelSpellId, 6000);

        // Act - the same signature happens twice in one combat log
        $this->runExtract([
            $this->npcCastSuccess(0, $channelSpellId, 'Solar Flame'),
            $this->debuffApplied(0, $channelSpellId, 'Solar Flame', self::CREATURE_GUID),
            $this->playerCastSuccess(1700, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->debuffRemoved(1700, $channelSpellId, 'Solar Flame', self::CREATURE_GUID),
            $this->npcCastSuccess(10000, $channelSpellId, 'Solar Flame'),
            $this->debuffApplied(10000, $channelSpellId, 'Solar Flame', self::CREATURE_GUID),
            $this->playerCastSuccess(11700, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->debuffRemoved(11700, $channelSpellId, 'Solar Flame', self::CREATURE_GUID),
        ]);

        // Assert
        $this->assertSame(1, $this->result->toArray()['addedSpellCounters']);
        $this->assertCounterRecorded($channelSpellId, Spell::COUNTER_VANISH, SpellProperty::CounterVanish);
    }

    #[Test]
    public function extractData_givenDebuffLastedItsFullDurationBeforeTheCounter_doesNotSetCounter(): void
    {
        // Arrange - same as the natural expiry case, but the removal line precedes the counter cast so the guard has
        // to reject it before it is ever held for the backward half of the window
        $castSpellId   = 9990017;
        $debuffSpellId = 9990018;
        $this->createTestSpell($castSpellId);
        $this->createTestSpell($debuffSpellId, 2000);

        // Act
        $this->runExtract([
            $this->npcCastStart(0, $castSpellId, 'Lens Flare'),
            $this->debuffApplied(0, $debuffSpellId, 'Lens Flare', null),
            $this->debuffRemoved(2000, $debuffSpellId, 'Lens Flare', null),
            $this->playerCastSuccess(2100, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellCounters']);
        $this->assertNoCounterRecorded($castSpellId);
        $this->assertNoCounterRecorded($debuffSpellId);
    }

    #[Test]
    public function extractData_givenDebuffOnAnotherPlayerThanTheCounterCaster_doesNotSetCounter(): void
    {
        // Arrange - 9990019 covers the removal-before-counter path, 9990020 the counter-before-removal path
        $earlyRemovalSpellId = 9990019;
        $lateRemovalSpellId  = 9990020;
        $this->createTestSpell($earlyRemovalSpellId, 6000);
        $this->createTestSpell($lateRemovalSpellId, 6000);

        // Act
        $this->runExtract([
            $this->npcCastSuccess(0, $earlyRemovalSpellId, 'Solar Flame'),
            $this->debuffApplied(0, $earlyRemovalSpellId, 'Solar Flame', self::CREATURE_GUID, self::OTHER_PLAYER_GUID),
            $this->debuffRemoved(1700, $earlyRemovalSpellId, 'Solar Flame', self::CREATURE_GUID, self::OTHER_PLAYER_GUID),
            $this->playerCastSuccess(1750, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),

            $this->npcCastSuccess(2000, $lateRemovalSpellId, 'Solar Flame'),
            $this->debuffApplied(2000, $lateRemovalSpellId, 'Solar Flame', self::CREATURE_GUID, self::OTHER_PLAYER_GUID),
            $this->playerCastSuccess(3000, VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST, 'Vanish'),
            $this->debuffRemoved(3050, $lateRemovalSpellId, 'Solar Flame', self::CREATURE_GUID, self::OTHER_PLAYER_GUID),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellCounters']);
        $this->assertNoCounterRecorded($earlyRemovalSpellId);
        $this->assertNoCounterRecorded($lateRemovalSpellId);
    }

    /**
     * Runs the full extract lifecycle: beforeExtract → extractData (one or more events) → afterExtract.
     *
     * @param BaseEvent[] $events
     */
    private function runExtract(array $events, string $combatLogPath = self::COMBAT_LOG_PATH): void
    {
        $this->extractor->beforeExtract($this->result, $combatLogPath);
        foreach ($events as $event) {
            $this->extractor->extractData($this->result, $this->currentDungeon, $event);
        }
        $this->extractor->afterExtract($this->result, $combatLogPath);
    }

    /**
     * Pins which of the three signatures the detection was attributed to - it is only observable through the log.
     */
    private function expectDetectionLoggedForSignature(string $signature, int $spellId, SpellProperty $property): void
    {
        /** @var Mockery\Expectation $expectation */
        $expectation = $this->log->shouldReceive('extractDataDetectedSpellCounter');
        $expectation->once()->with($signature, $spellId, $property->value, self::PLAYER_GUID);
    }

    private function assertCounterRecorded(int $spellId, int $counterBit, SpellProperty $property): void
    {
        $this->assertDatabaseHas('spells', [
            'id'            => $spellId,
            'counters_mask' => $counterBit,
        ]);

        $this->assertDatabaseHas('combat_log_spell_property_observations', [
            'spell_id'        => $spellId,
            'property'        => $property->value,
            'combat_log_path' => self::COMBAT_LOG_PATH,
        ], 'combatlog');

        $this->assertSame(
            1,
            CombatLogSpellEvent::where('spell_id', $spellId)
                ->where('event_type', CombatLogSpellEventType::PropertyChanged)
                ->where('property', $property)
                ->count(),
        );
    }

    private function assertNoCounterRecorded(int $spellId): void
    {
        $this->assertDatabaseHas('spells', [
            'id'            => $spellId,
            'counters_mask' => 0,
        ]);

        $this->assertDatabaseMissing('combat_log_spell_property_observations', [
            'spell_id' => $spellId,
        ], 'combatlog');

        $this->assertDatabaseMissing('combat_log_spell_events', [
            'spell_id' => $spellId,
        ], 'combatlog');
    }

    private function createTestSpell(int $spellId, ?int $duration = null, ?string $category = null): Spell
    {
        return Spell::create([
            'id'              => $spellId,
            'game_version_id' => 1,
            'category'        => $category,
            'dispel_type'     => 'none',
            'icon_name'       => 'inv_misc_questionmark',
            'name'            => sprintf('Test Spell %d', $spellId),
            'schools_mask'    => 1,
            'duration'        => $duration,
        ]);
    }

    private function cleanUpTestData(): void
    {
        CombatLogSpellPropertyObservation::whereBetween('spell_id', [self::TEST_SPELL_ID_FIRST, self::TEST_SPELL_ID_LAST])->delete();
        CombatLogSpellEvent::whereBetween('spell_id', [self::TEST_SPELL_ID_FIRST, self::TEST_SPELL_ID_LAST])->delete();
        CombatLogNpcEvent::where('model_class', Spell::class)->whereBetween('model_id', [self::TEST_SPELL_ID_FIRST, self::TEST_SPELL_ID_LAST])->delete();
        NpcSpell::query()->whereBetween('spell_id', [self::TEST_SPELL_ID_FIRST, self::TEST_SPELL_ID_LAST])->delete();
        SpellDungeon::query()->whereBetween('spell_id', [self::TEST_SPELL_ID_FIRST, self::TEST_SPELL_ID_LAST])->delete();
        Spell::query()->whereBetween('id', [self::TEST_SPELL_ID_FIRST, self::TEST_SPELL_ID_LAST])->delete();
    }

    private function npcCastStart(int $offsetMs, int $spellId, string $spellName, string $casterGuid = self::CREATURE_GUID): BaseEvent
    {
        return $this->parse(sprintf(
            '%s  SPELL_CAST_START,%s,%s,%d,"%s",0x8',
            $this->timestamp($offsetMs),
            $this->actorFields($casterGuid),
            $this->actorFields(null),
            $spellId,
            $spellName,
        ));
    }

    private function npcCastSuccess(int $offsetMs, int $spellId, string $spellName, string $casterGuid = self::CREATURE_GUID): BaseEvent
    {
        return $this->parse(sprintf(
            '%s  SPELL_CAST_SUCCESS,%s,%s,%d,"%s",0x8,%s',
            $this->timestamp($offsetMs),
            $this->actorFields($casterGuid),
            $this->actorFields(null),
            $spellId,
            $spellName,
            $this->advancedFields($casterGuid),
        ));
    }

    private function playerCastSuccess(int $offsetMs, int $spellId, string $spellName, string $playerGuid = self::PLAYER_GUID): BaseEvent
    {
        return $this->parse(sprintf(
            '%s  SPELL_CAST_SUCCESS,%s,%s,%d,"%s",0x1,%s',
            $this->timestamp($offsetMs),
            $this->actorFields($playerGuid),
            $this->actorFields(null),
            $spellId,
            $spellName,
            $this->advancedFields($playerGuid),
        ));
    }

    private function debuffApplied(
        int     $offsetMs,
        int     $spellId,
        string  $spellName,
        ?string $sourceGuid,
        string  $destGuid = self::PLAYER_GUID,
    ): BaseEvent {
        return $this->parse(sprintf(
            '%s  SPELL_AURA_APPLIED,%s,%s,%d,"%s",0x4,DEBUFF',
            $this->timestamp($offsetMs),
            $this->actorFields($sourceGuid),
            $this->actorFields($destGuid),
            $spellId,
            $spellName,
        ));
    }

    private function debuffRefreshed(
        int     $offsetMs,
        int     $spellId,
        string  $spellName,
        ?string $sourceGuid,
        string  $destGuid = self::PLAYER_GUID,
    ): BaseEvent {
        return $this->parse(sprintf(
            '%s  SPELL_AURA_REFRESH,%s,%s,%d,"%s",0x4,DEBUFF',
            $this->timestamp($offsetMs),
            $this->actorFields($sourceGuid),
            $this->actorFields($destGuid),
            $spellId,
            $spellName,
        ));
    }

    private function debuffRemoved(
        int     $offsetMs,
        int     $spellId,
        string  $spellName,
        ?string $sourceGuid,
        string  $destGuid = self::PLAYER_GUID,
    ): BaseEvent {
        return $this->parse(sprintf(
            '%s  SPELL_AURA_REMOVED,%s,%s,%d,"%s",0x4,DEBUFF',
            $this->timestamp($offsetMs),
            $this->actorFields($sourceGuid),
            $this->actorFields($destGuid),
            $spellId,
            $spellName,
        ));
    }

    /**
     * A player interrupts the NPC - the interrupted caster is the *destination* of the event.
     */
    private function interrupt(int $offsetMs, int $interruptedSpellId, string $interruptedSpellName): BaseEvent
    {
        return $this->parse(sprintf(
            '%s  SPELL_INTERRUPT,%s,%s,47528,"Mind Freeze",0x20,%d,"%s",8',
            $this->timestamp($offsetMs),
            $this->actorFields(self::PLAYER_GUID),
            $this->actorFields(self::CREATURE_GUID),
            $interruptedSpellId,
            $interruptedSpellName,
        ));
    }

    private function unitDied(int $offsetMs, string $creatureGuid = self::CREATURE_GUID): BaseEvent
    {
        return $this->parse(sprintf(
            '%s  UNIT_DIED,%s,%s,0',
            $this->timestamp($offsetMs),
            $this->actorFields(null),
            $this->actorFields($creatureGuid),
        ));
    }

    /**
     * The 4 generic-data fields (guid, name, flags, raid flags) for one side of an event. A null guid produces the
     * nil-guid form that targeting debuffs and destination-less casts are logged with.
     */
    private function actorFields(?string $guid): string
    {
        if ($guid === null) {
            return '0000000000000000,nil,0x80000000,0x80000000';
        }

        if (str_starts_with($guid, 'Player-')) {
            return sprintf('%s,"Jaxeek-TarrenMill-EU",0x511,0x80000000', $guid);
        }

        return sprintf('%s,"Dread Raven",0xa48,0x80000000', $guid);
    }

    /**
     * The 19 advanced-logging fields that a SPELL_CAST_SUCCESS carries on RETAIL_11_0_5.
     */
    private function advancedFields(string $infoGuid): string
    {
        return sprintf(
            '%s,0000000000000000,436040,436040,3094,437,859,100,413,35241,3,90,100,0,1238.22,1700.90,601,5.7883,287',
            $infoGuid,
        );
    }

    private function timestamp(int $offsetMs): string
    {
        return sprintf('%s-4', Carbon::create(2024, 8, 2, 16, 24, 18)->addMilliseconds($offsetMs)->format('n/j/Y H:i:s.v'));
    }

    private function parse(string $rawEvent): BaseEvent
    {
        return new CombatLogEntry($rawEvent)->parseEvent([], CombatLogVersion::RETAIL_11_0_5);
    }
}
