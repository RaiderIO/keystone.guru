<?php

namespace Tests\Feature\App\Console\Commands\CombatLog;

use App\Console\Commands\CombatLog\DetectStaleCombatLogDataCommand;
use App\Models\Characteristic;
use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\CombatLog\SpellProperty;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcCharacteristic;
use App\Models\Npc\NpcDungeon;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use App\Service\Season\SeasonServiceInterface;
use App\Service\Season\SeasonServiceStub;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\TestCases\PublicTestCase;

#[Group('DetectStaleCombatLogDataCommand')]
#[SlowTest]
final class DetectStaleCombatLogDataCommandTest extends PublicTestCase
{
    private const int NPC_ID        = 9995099;
    private const int SPELL_ID      = 9995098;
    private const int FILLER_NPC_ID = 9995096;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // The staleness/prune cutoffs are now derived from the full, global contents of both
        // observation tables, so a leftover row from real data or a sibling test would silently
        // shift which index a date lands on. Wrap both connections in a transaction for the
        // duration of the test and roll back in tearDown() — this both empties the observation
        // tables for a clean, controlled slate AND undoes any real npc_characteristics/spells
        // rows the command mutates while scanning every current-season fact against that empty
        // slate (those rows are combat-log-derived and not recoverable, so a plain
        // truncate-and-restore of only the observation tables is not safe here).
        // The default connection is 'phpunit' in the test environment (config/database.php), not
        // 'mysql' — use the unqualified DB facade so this targets whichever connection the models
        // actually resolve to.
        DB::beginTransaction();
        DB::connection('combatlog')->beginTransaction();

        CombatLogNpcCharacteristicObservation::query()->toBase()->delete();
        CombatLogSpellPropertyObservation::query()->toBase()->delete();
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            DB::rollBack();
            DB::connection('combatlog')->rollBack();
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
            'aggressiveness'    => Npc::AGGRESSIVENESS_AGGRESSIVE,
            'dangerous'         => 0,
            'truesight'         => 0,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createTestSpell(array $overrides = []): Spell
    {
        return Spell::create(array_merge([
            'id'              => self::SPELL_ID,
            'game_version_id' => 1,
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

    private function createNpcCharacteristicObservation(Carbon $observedOn): void
    {
        CombatLogNpcCharacteristicObservation::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH],
            'observed_on'       => $observedOn->toDateString(),
            'combat_log_path'   => '/tmp/test.log',
        ]);
    }

    private function createSpellPropertyObservation(SpellProperty $property, Carbon $observedOn): void
    {
        CombatLogSpellPropertyObservation::create([
            'spell_id'        => self::SPELL_ID,
            'property'        => $property,
            'observed_on'     => $observedOn->toDateString(),
            'combat_log_path' => '/tmp/test.log',
        ]);
    }

    /**
     * Seeds $count distinct data-days (unrelated to the NPC/spell under test) so the command has
     * enough observation history to derive a staleness/prune cutoff from. Days run from
     * $startDaysAgo back to $startDaysAgo + $count - 1, relative to $mostRecent (defaults to now).
     */
    private function seedObservationDays(int $count, int $startDaysAgo = 0, ?Carbon $mostRecent = null): void
    {
        $mostRecent ??= now();

        for ($i = 0; $i < $count; $i++) {
            CombatLogNpcCharacteristicObservation::create([
                'npc_id'            => self::FILLER_NPC_ID,
                'characteristic_id' => Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH],
                'observed_on'       => $mostRecent->copy()->subDays($startDaysAgo + $i)->toDateString(),
                'combat_log_path'   => '/tmp/filler.log',
            ]);
        }
    }

    private function getCurrentSeasonDungeonId(): int
    {
        return app(SeasonServiceInterface::class)
            ->getCurrentSeason()
            ->seasonDungeons()
            ->value('dungeon_id');
    }

    private function linkNpcToCurrentSeason(): void
    {
        NpcDungeon::create([
            'npc_id'     => self::NPC_ID,
            'dungeon_id' => $this->getCurrentSeasonDungeonId(),
        ]);
    }

    private function linkSpellToCurrentSeason(): void
    {
        SpellDungeon::create([
            'spell_id'   => self::SPELL_ID,
            'dungeon_id' => $this->getCurrentSeasonDungeonId(),
        ]);
    }

    #[Test]
    public function handle_givenStaleNpcCharacteristic_removesNpcCharacteristicAndCreatesRemovedEvent(): void
    {
        // Arrange
        $windowDays       = config('keystoneguru.combat_log_staleness.observation_window_days');
        $characteristicId = Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH];
        $this->seedObservationDays($windowDays + 1);
        $this->createTestNpc();
        $this->linkNpcToCurrentSeason();
        NpcCharacteristic::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
        // Outside the populated window entirely — genuinely stale, not merely recent-but-old.
        $this->createNpcCharacteristicObservation(now()->subDays($windowDays + 10));

        // Act
        $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseMissing('npc_characteristics', [
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
        $this->assertDatabaseHas('combat_log_npc_events', [
            'npc_id'      => self::NPC_ID,
            'event_type'  => CombatLogNpcEventType::CharacteristicRemoved->value,
            'model_class' => Characteristic::class,
            'model_id'    => $characteristicId,
        ], 'combatlog');
    }

    #[Test]
    public function handle_givenFreshNpcCharacteristic_doesNothing(): void
    {
        // Arrange
        $windowDays       = config('keystoneguru.combat_log_staleness.observation_window_days');
        $characteristicId = Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH];
        $this->createTestNpc();
        $this->linkNpcToCurrentSeason();
        NpcCharacteristic::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
        // A populated window whose most recent data-day is the fact's own observation.
        $this->seedObservationDays($windowDays, 1);
        $this->createNpcCharacteristicObservation(now());

        // Act
        $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('npc_characteristics', [
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
        $this->assertDatabaseMissing('combat_log_npc_events', [
            'npc_id'     => self::NPC_ID,
            'event_type' => CombatLogNpcEventType::CharacteristicRemoved->value,
        ], 'combatlog');
    }

    #[Test]
    public function handle_givenStaleNpcCharacteristicNotInCurrentSeason_keepsNpcCharacteristic(): void
    {
        // Arrange
        $windowDays       = config('keystoneguru.combat_log_staleness.observation_window_days');
        $characteristicId = Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH];
        $this->seedObservationDays($windowDays + 1);
        $this->createTestNpc();
        // Deliberately do NOT link NPC to any current-season dungeon
        NpcCharacteristic::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
        $this->createNpcCharacteristicObservation(now()->subDays($windowDays + 10));

        // Act
        $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

        // Assert — characteristic must be preserved because the NPC is not in the current season
        $this->assertDatabaseHas('npc_characteristics', [
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
        $this->assertDatabaseMissing('combat_log_npc_events', [
            'npc_id'     => self::NPC_ID,
            'event_type' => CombatLogNpcEventType::CharacteristicRemoved->value,
        ], 'combatlog');
    }

    #[Test]
    public function handle_givenStaleSpellProperty_clearsPropertyAndCreatesRemovedEvent(): void
    {
        // Arrange
        $windowDays = config('keystoneguru.combat_log_staleness.observation_window_days');
        $this->seedObservationDays($windowDays + 1);
        $this->createTestSpell(['aura' => true]);
        $this->linkSpellToCurrentSeason();
        $this->createSpellPropertyObservation(
            SpellProperty::Aura,
            now()->subDays($windowDays + 10),
        );

        // Act
        $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('spells', ['id' => self::SPELL_ID, 'aura' => false]);
        $this->assertDatabaseHas('combat_log_spell_events', [
            'spell_id'   => self::SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyRemoved->value,
            'property'   => SpellProperty::Aura->value,
        ], 'combatlog');
    }

    #[Test]
    public function handle_givenFreshSpellProperty_doesNothing(): void
    {
        // Arrange
        $windowDays = config('keystoneguru.combat_log_staleness.observation_window_days');
        $this->createTestSpell(['aura' => true]);
        $this->linkSpellToCurrentSeason();
        $this->seedObservationDays($windowDays, 1);
        $this->createSpellPropertyObservation(SpellProperty::Aura, now());

        // Act
        $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('spells', ['id' => self::SPELL_ID, 'aura' => true]);
        $this->assertDatabaseMissing('combat_log_spell_events', [
            'spell_id'   => self::SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyRemoved->value,
        ], 'combatlog');
    }

    #[Test]
    public function handle_givenStaleSpellCounter_clearsCounterBitAndCreatesRemovedEvent(): void
    {
        // Arrange
        $windowDays = config('keystoneguru.combat_log_staleness.observation_window_days');
        $this->seedObservationDays($windowDays + 1);
        $this->createTestSpell(['counters_mask' => Spell::COUNTER_VANISH]);
        $this->linkSpellToCurrentSeason();
        $this->createSpellPropertyObservation(
            SpellProperty::CounterVanish,
            now()->subDays($windowDays + 10),
        );

        // Act
        $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('spells', ['id' => self::SPELL_ID, 'counters_mask' => 0]);
        $this->assertDatabaseHas('combat_log_spell_events', [
            'spell_id'   => self::SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyRemoved->value,
            'property'   => SpellProperty::CounterVanish->value,
        ], 'combatlog');
    }

    #[Test]
    public function handle_givenFreshSpellCounter_doesNothing(): void
    {
        // Arrange
        $windowDays = config('keystoneguru.combat_log_staleness.observation_window_days');
        $this->createTestSpell(['counters_mask' => Spell::COUNTER_VANISH]);
        $this->linkSpellToCurrentSeason();
        $this->seedObservationDays($windowDays, 1);
        $this->createSpellPropertyObservation(SpellProperty::CounterVanish, now());

        // Act
        $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('spells', ['id' => self::SPELL_ID, 'counters_mask' => Spell::COUNTER_VANISH]);
        $this->assertDatabaseMissing('combat_log_spell_events', [
            'spell_id'   => self::SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyRemoved->value,
            'property'   => SpellProperty::CounterVanish->value,
        ], 'combatlog');
    }

    #[Test]
    public function handle_givenStaleImmunityBypass_clearsImmunityBitAndCreatesRemovedEvent(): void
    {
        // Arrange
        $windowDays = config('keystoneguru.combat_log_staleness.observation_window_days');
        $this->seedObservationDays($windowDays + 1);
        $this->createTestSpell(['bypasses_immunities_mask' => Spell::IMMUNITY_DIVINE_SHIELD]);
        $this->linkSpellToCurrentSeason();
        $this->createSpellPropertyObservation(
            SpellProperty::BypassDivineShield,
            now()->subDays($windowDays + 10),
        );

        // Act
        $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('spells', ['id' => self::SPELL_ID, 'bypasses_immunities_mask' => 0]);
        $this->assertDatabaseHas('combat_log_spell_events', [
            'spell_id'   => self::SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyRemoved->value,
            'property'   => SpellProperty::BypassDivineShield->value,
        ], 'combatlog');
    }

    #[Test]
    public function handle_givenFreshImmunityBypass_doesNothing(): void
    {
        // Arrange
        $windowDays = config('keystoneguru.combat_log_staleness.observation_window_days');
        $this->createTestSpell(['bypasses_immunities_mask' => Spell::IMMUNITY_DIVINE_SHIELD]);
        $this->linkSpellToCurrentSeason();
        $this->seedObservationDays($windowDays, 1);
        $this->createSpellPropertyObservation(SpellProperty::BypassDivineShield, now());

        // Act
        $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('spells', ['id' => self::SPELL_ID, 'bypasses_immunities_mask' => Spell::IMMUNITY_DIVINE_SHIELD]);
        $this->assertDatabaseMissing('combat_log_spell_events', [
            'spell_id'   => self::SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyRemoved->value,
            'property'   => SpellProperty::BypassDivineShield->value,
        ], 'combatlog');
    }

    #[Test]
    public function handle_givenStaleSpellPropertyNotInCurrentSeason_keepsSpellProperty(): void
    {
        // Arrange
        $windowDays = config('keystoneguru.combat_log_staleness.observation_window_days');
        $this->seedObservationDays($windowDays + 1);
        $this->createTestSpell(['aura' => true]);
        // Deliberately do NOT link Spell to any current-season dungeon
        $this->createSpellPropertyObservation(
            SpellProperty::Aura,
            now()->subDays($windowDays + 10),
        );

        // Act
        $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

        // Assert — property must be preserved because the spell is not in the current season
        $this->assertDatabaseHas('spells', ['id' => self::SPELL_ID, 'aura' => true]);
        $this->assertDatabaseMissing('combat_log_spell_events', [
            'spell_id'   => self::SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyRemoved->value,
        ], 'combatlog');
    }

    #[Test]
    public function handle_givenNoCurrentSeason_skipsStaleDetectionButPrunesObservations(): void
    {
        // Arrange
        config(['keystoneguru.combat_log_staleness.observation_window_days' => 3]);
        $this->app->instance(SeasonServiceInterface::class, new SeasonServiceStub());

        // A continuous 5-day window (today .. 4 days ago) so the prune cutoff lands at 4 days ago.
        $this->seedObservationDays(5);

        $characteristicId = Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH];
        $this->createTestNpc();
        NpcCharacteristic::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
        // Stale observation (will be pruned, older than the 4-day prune cutoff) and a fresh one
        // (will be kept, within the prune cutoff).
        $this->createNpcCharacteristicObservation(now()->subDays(5));
        $this->createNpcCharacteristicObservation(now()->subDays(3));

        // Act
        $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

        // Assert — characteristic kept (no season → skip stale detection)
        $this->assertDatabaseHas('npc_characteristics', [
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
        // Assert — old observation pruned, recent one kept
        $this->assertSame(1, CombatLogNpcCharacteristicObservation::where('npc_id', self::NPC_ID)->count());
    }

    #[Test]
    public function handle_givenNoObservationsToday_keepsDataAndCreatesNoRemovedEvents(): void
    {
        // Arrange
        $windowDays = config('keystoneguru.combat_log_staleness.observation_window_days');
        $baseline   = now();

        // A populated window ending "today" (the baseline), with the facts under test observed
        // on that same baseline day.
        $this->seedObservationDays($windowDays + 1, 0, $baseline);

        $characteristicId = Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH];
        $this->createTestNpc();
        $this->linkNpcToCurrentSeason();
        NpcCharacteristic::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
        $this->createNpcCharacteristicObservation($baseline);

        $this->createTestSpell(['aura' => true]);
        $this->linkSpellToCurrentSeason();
        $this->createSpellPropertyObservation(SpellProperty::Aura, $baseline);

        try {
            // Simulate a drought: several days pass with no new ingest at all, so no new data-day
            // ever enters the observation tables.
            Carbon::setTestNow($baseline->copy()->addDays($windowDays + 3));

            // Act
            $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

            // Assert — the Compendium freezes: nothing decayed because the cutoff never moved.
            $this->assertDatabaseHas('npc_characteristics', [
                'npc_id'            => self::NPC_ID,
                'characteristic_id' => $characteristicId,
            ]);
            $this->assertDatabaseMissing('combat_log_npc_events', [
                'npc_id'     => self::NPC_ID,
                'event_type' => CombatLogNpcEventType::CharacteristicRemoved->value,
            ], 'combatlog');

            $this->assertDatabaseHas('spells', ['id' => self::SPELL_ID, 'aura' => true]);
            $this->assertDatabaseMissing('combat_log_spell_events', [
                'spell_id'   => self::SPELL_ID,
                'event_type' => CombatLogSpellEventType::PropertyRemoved->value,
            ], 'combatlog');
        } finally {
            Carbon::setTestNow();
        }
    }

    #[Test]
    public function handle_givenContinuousIngest_removesStaleDataAsBefore(): void
    {
        // Arrange — regression guard on the cutoff index: under continuous ingest, a fact last
        // observed exactly window+1 data-days ago must still be removed.
        $windowDays = config('keystoneguru.combat_log_staleness.observation_window_days');
        $this->seedObservationDays($windowDays + 2);

        $characteristicId = Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH];
        $this->createTestNpc();
        $this->linkNpcToCurrentSeason();
        NpcCharacteristic::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
        $this->createNpcCharacteristicObservation(now()->subDays($windowDays + 1));

        // Act
        $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseMissing('npc_characteristics', [
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
        $this->assertDatabaseHas('combat_log_npc_events', [
            'npc_id'      => self::NPC_ID,
            'event_type'  => CombatLogNpcEventType::CharacteristicRemoved->value,
            'model_class' => Characteristic::class,
            'model_id'    => $characteristicId,
        ], 'combatlog');
    }

    #[Test]
    public function handle_givenFewerObservationDaysThanWindow_removesNothing(): void
    {
        // Arrange — only window_days distinct data-days exist, one short of the window+1 needed
        // to define a staleness cutoff at all.
        $windowDays = config('keystoneguru.combat_log_staleness.observation_window_days');
        $this->seedObservationDays($windowDays);

        $characteristicId = Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH];
        $this->createTestNpc();
        $this->linkNpcToCurrentSeason();
        NpcCharacteristic::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);

        // Act
        $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

        // Assert — not enough evidence to call anything stale
        $this->assertDatabaseHas('npc_characteristics', [
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
        $this->assertDatabaseMissing('combat_log_npc_events', [
            'npc_id'     => self::NPC_ID,
            'event_type' => CombatLogNpcEventType::CharacteristicRemoved->value,
        ], 'combatlog');
    }

    #[Test]
    public function handle_givenFewerObservationDaysThanWindowPlusOne_prunesNothing(): void
    {
        // Arrange — window_days+1 distinct data-days exist, one short of the window_days+2 needed
        // to define a prune cutoff at all. Offset them well into the past (not ending "today") so
        // the old calendar-based cutoff would have pruned every one of them — otherwise this test
        // would pass vacuously even with the null-guard removed.
        $windowDays = config('keystoneguru.combat_log_staleness.observation_window_days');
        $this->seedObservationDays($windowDays + 1, 10);

        $countBefore = CombatLogNpcCharacteristicObservation::count();

        // Act
        $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

        // Assert — not enough evidence to prune anything, even the oldest of these rows
        $this->assertSame($countBefore, CombatLogNpcCharacteristicObservation::count());
    }

    #[Test]
    public function handle_prunesObservationsOlderThanFourDays(): void
    {
        // Arrange — pin the observation window to 3 so the derived cutoffs are predictable
        config(['keystoneguru.combat_log_staleness.observation_window_days' => 3]);

        // A continuous 5-day window (today .. 4 days ago) so the staleness cutoff lands at
        // 3 days ago and the prune cutoff at 4 days ago.
        $this->seedObservationDays(5);

        $this->createTestNpc();
        $this->linkNpcToCurrentSeason();
        NpcCharacteristic::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH],
        ]);

        // 3 days old → at the staleness cutoff, and within the prune window → kept
        $this->createNpcCharacteristicObservation(now()->subDays(3));
        // 5 days old → older than the prune cutoff (4 days ago) → pruned
        CombatLogNpcCharacteristicObservation::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH],
            'observed_on'       => now()->subDays(5)->toDateString(),
            'combat_log_path'   => '/tmp/old.log',
        ]);

        $this->createTestSpell(['aura' => true]);
        $this->linkSpellToCurrentSeason();
        // 3 days old → kept
        $this->createSpellPropertyObservation(SpellProperty::Aura, now()->subDays(3));
        // 5 days old → pruned
        CombatLogSpellPropertyObservation::create([
            'spell_id'        => self::SPELL_ID,
            'property'        => SpellProperty::Aura,
            'observed_on'     => now()->subDays(5)->toDateString(),
            'combat_log_path' => '/tmp/old.log',
        ]);

        // Act
        $this->artisan(DetectStaleCombatLogDataCommand::class)->assertSuccessful();

        // Assert NPC observations: 3-day-old kept, 5-day-old pruned
        $this->assertSame(
            1,
            CombatLogNpcCharacteristicObservation::where('npc_id', self::NPC_ID)->count(),
        );
        $this->assertDatabaseHas('combat_log_npc_characteristic_observations', [
            'npc_id'          => self::NPC_ID,
            'combat_log_path' => '/tmp/test.log',
        ], 'combatlog');

        // Assert spell observations: 3-day-old kept, 5-day-old pruned
        $this->assertSame(
            1,
            CombatLogSpellPropertyObservation::where('spell_id', self::SPELL_ID)->count(),
        );
        $this->assertDatabaseHas('combat_log_spell_property_observations', [
            'spell_id'        => self::SPELL_ID,
            'combat_log_path' => '/tmp/test.log',
        ], 'combatlog');
    }
}
