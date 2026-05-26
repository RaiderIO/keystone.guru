<?php

namespace Tests\Feature\App\Console\Commands\CombatLog;

use App\Console\Commands\CombatLog\DetectStaleCombatLogDataCommand;
use App\Models\Characteristic;
use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\CombatLog\CombatLogSpellEvent;
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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DetectStaleCombatLogDataCommand')]
final class DetectStaleCombatLogDataCommandTest extends PublicTestCase
{
    private const int NPC_ID   = 9995099;
    private const int SPELL_ID = 9995098;

    protected function tearDown(): void
    {
        try {
            NpcDungeon::where('npc_id', self::NPC_ID)->delete();
            SpellDungeon::where('spell_id', self::SPELL_ID)->delete();
            NpcCharacteristic::where('npc_id', self::NPC_ID)->delete();
            Npc::where('id', self::NPC_ID)->delete();
            CombatLogNpcCharacteristicObservation::where('npc_id', self::NPC_ID)->delete();
            CombatLogNpcEvent::where('npc_id', self::NPC_ID)->delete();
            CombatLogSpellPropertyObservation::where('spell_id', self::SPELL_ID)->delete();
            CombatLogSpellEvent::where('spell_id', self::SPELL_ID)->delete();
            Spell::where('id', self::SPELL_ID)->delete();
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
        $characteristicId = Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH];
        $this->createTestNpc();
        $this->linkNpcToCurrentSeason();
        NpcCharacteristic::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
        $this->createNpcCharacteristicObservation(now()->subDays(config('keystoneguru.combat_log_staleness.observation_window_days') + 1));

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
        $characteristicId = Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH];
        $this->createTestNpc();
        $this->linkNpcToCurrentSeason();
        NpcCharacteristic::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
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
        $characteristicId = Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH];
        $this->createTestNpc();
        // Deliberately do NOT link NPC to any current-season dungeon
        NpcCharacteristic::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
        $this->createNpcCharacteristicObservation(now()->subDays(config('keystoneguru.combat_log_staleness.observation_window_days') + 1));

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
        $this->createTestSpell(['aura' => true]);
        $this->linkSpellToCurrentSeason();
        $this->createSpellPropertyObservation(
            SpellProperty::Aura,
            now()->subDays(config('keystoneguru.combat_log_staleness.observation_window_days') + 1),
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
        $this->createTestSpell(['aura' => true]);
        $this->linkSpellToCurrentSeason();
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
    public function handle_givenStaleSpellPropertyNotInCurrentSeason_keepsSpellProperty(): void
    {
        // Arrange
        $this->createTestSpell(['aura' => true]);
        // Deliberately do NOT link Spell to any current-season dungeon
        $this->createSpellPropertyObservation(
            SpellProperty::Aura,
            now()->subDays(config('keystoneguru.combat_log_staleness.observation_window_days') + 1),
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

        $characteristicId = Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH];
        $this->createTestNpc();
        NpcCharacteristic::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => $characteristicId,
        ]);
        // Stale observation (will be pruned) and a fresh one (will be kept)
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
    public function handle_prunesObservationsOlderThanFourDays(): void
    {
        // Arrange — pin the observation window to 3 so the prune threshold is predictable regardless of .env
        config(['keystoneguru.combat_log_staleness.observation_window_days' => 3]);

        // create observations at different ages
        $this->createTestNpc();
        $this->linkNpcToCurrentSeason();
        NpcCharacteristic::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH],
        ]);

        // 3 days old → should be kept (within prune window)
        $this->createNpcCharacteristicObservation(now()->subDays(3));
        // 5 days old → should be pruned (outside prune window of 4 days)
        CombatLogNpcCharacteristicObservation::create([
            'npc_id'            => self::NPC_ID,
            'characteristic_id' => Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH],
            'observed_on'       => now()->subDays(5)->toDateString(),
            'combat_log_path'   => '/tmp/old.log',
        ]);

        $this->createTestSpell(['aura' => true]);
        $this->linkSpellToCurrentSeason();
        // 3 days old → should be kept
        $this->createSpellPropertyObservation(SpellProperty::Aura, now()->subDays(3));
        // 5 days old → should be pruned
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
