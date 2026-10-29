<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Models\Characteristic;
use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\CombatLog\SpellProperty;
use App\Service\CombatLog\CombatLogActivityEventServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLogActivityEventService')]
final class CombatLogActivityEventServiceTest extends PublicTestCase
{
    private const int SPELL_ID = 9995198;
    private const int NPC_ID   = 9995199;

    private const string COMBAT_LOG_PATH = '/tmp/activity-event-test.log';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Events and observations are combat-log-derived; roll the combatlog connection back rather than deleting
        DB::connection('combatlog')->beginTransaction();

        config([
            'keystoneguru.combat_log_staleness.event_density_gate_enabled'      => true,
            'keystoneguru.combat_log_staleness.event_min_subject_days'          => 7,
            'keystoneguru.combat_log_staleness.event_min_new_fact_subject_days' => 1,
            'keystoneguru.combat_log_staleness.event_min_fact_ratio'            => 0.5,
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            DB::connection('combatlog')->rollBack();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function recordSpellPropertyAdded_givenSubjectNotObservedBeforeThatDay_writesNoEvent(): void
    {
        // Arrange - the spell's own first observed day: everything about it is new at once, which is the season
        // opening rather than news
        $this->observeSpell(SpellProperty::Aura, [0]);

        // Act
        $event = $this->service()->recordSpellPropertyAdded(self::SPELL_ID, SpellProperty::MissImmune, self::COMBAT_LOG_PATH, Carbon::today());

        // Assert
        $this->assertNull($event);
        $this->assertSame(0, $this->spellEventCount(CombatLogSpellEventType::PropertyChanged));
    }

    #[Test]
    public function recordSpellPropertyAdded_givenNewFactOnSubjectObservedOnOneEarlierDay_writesEventWithEvidence(): void
    {
        // Arrange - one earlier day is enough history to tell a discovery from the spell's own first sighting
        $this->observeSpell(SpellProperty::Aura, [1]);

        // Act
        $event = $this->service()->recordSpellPropertyAdded(self::SPELL_ID, SpellProperty::MissImmune, self::COMBAT_LOG_PATH, Carbon::today());

        // Assert
        $this->assertNotNull($event);
        $this->assertSame(0, $event->fact_days);
        $this->assertSame(1, $event->subject_days);
    }

    #[Test]
    public function recordSpellPropertyAdded_givenRareFactReturningOnYoungSubject_writesNoEvent(): void
    {
        // Arrange - a fact that expired and came back still needs the full subject history, which the one-day
        // allowance for brand new facts does not grant it
        $this->observeSpell(SpellProperty::Aura, range(1, 3));
        $this->observeSpell(SpellProperty::MissImmune, [1]);

        // Act
        $event = $this->service()->recordSpellPropertyAdded(self::SPELL_ID, SpellProperty::MissImmune, self::COMBAT_LOG_PATH, Carbon::today());

        // Assert
        $this->assertNull($event);
        $this->assertSame(0, $this->spellEventCount(CombatLogSpellEventType::PropertyChanged));
    }

    #[Test]
    public function recordSpellPropertyAdded_givenNewFactOnLongObservedSubject_writesEventWithEvidence(): void
    {
        // Arrange
        $this->observeSpell(SpellProperty::Aura, range(1, 7));

        // Act
        $event = $this->service()->recordSpellPropertyAdded(self::SPELL_ID, SpellProperty::MissImmune, self::COMBAT_LOG_PATH, Carbon::today());

        // Assert
        $this->assertNotNull($event);
        $this->assertDatabaseHas('combat_log_spell_events', [
            'spell_id'        => self::SPELL_ID,
            'event_type'      => CombatLogSpellEventType::PropertyChanged->value,
            'property'        => SpellProperty::MissImmune->value,
            'combat_log_path' => self::COMBAT_LOG_PATH,
            'fact_days'       => 0,
            'subject_days'    => 7,
        ], 'combatlog');
    }

    #[Test]
    public function recordSpellPropertyAdded_givenTodaysObservationsWouldReachMinDays_writesNoEvent(): void
    {
        // Arrange - six earlier days plus today: today is the observation being reported, not history. Counting
        // today would make 7 subject days with the fact on 4 of them, which passes both thresholds
        $this->observeSpell(SpellProperty::Aura, range(0, 6));
        $this->observeSpell(SpellProperty::MissImmune, range(0, 3));

        // Act
        $event = $this->service()->recordSpellPropertyAdded(self::SPELL_ID, SpellProperty::MissImmune, self::COMBAT_LOG_PATH, Carbon::today());

        // Assert
        $this->assertNull($event);
        $this->assertSame(0, $this->spellEventCount(CombatLogSpellEventType::PropertyChanged));
    }

    #[Test]
    public function recordSpellPropertyAdded_givenObservationWrittenOnAnEarlierDate_countsOnlyTheDaysBeforeThatDate(): void
    {
        // Arrange - the observation was stamped yesterday and the clock has since passed midnight; counting yesterday
        // as history would make the fact 1 of 8 days instead of new
        $this->observeSpell(SpellProperty::Aura, range(1, 8));
        $this->observeSpell(SpellProperty::MissImmune, [1]);

        // Act
        $event = $this->service()->recordSpellPropertyAdded(self::SPELL_ID, SpellProperty::MissImmune, self::COMBAT_LOG_PATH, Carbon::yesterday());

        // Assert
        $this->assertNotNull($event);
        $this->assertSame(0, $event->fact_days);
        $this->assertSame(7, $event->subject_days);
    }

    #[Test]
    public function recordSpellPropertyAdded_givenRareFactReturning_writesNoEvent(): void
    {
        // Arrange - seen on 2 of the spell's 10 days
        $this->observeSpell(SpellProperty::Aura, range(1, 10));
        $this->observeSpell(SpellProperty::MissImmune, [9, 10]);

        // Act
        $event = $this->service()->recordSpellPropertyAdded(self::SPELL_ID, SpellProperty::MissImmune, self::COMBAT_LOG_PATH, Carbon::today());

        // Assert
        $this->assertNull($event);
        $this->assertSame(0, $this->spellEventCount(CombatLogSpellEventType::PropertyChanged));
    }

    #[Test]
    public function recordSpellPropertyAdded_givenFactSeenOnHalfTheSubjectDays_writesEventWithEvidence(): void
    {
        // Arrange - exactly at the ratio threshold
        $this->observeSpell(SpellProperty::Aura, range(1, 10));
        $this->observeSpell(SpellProperty::MissImmune, range(6, 10));

        // Act
        $event = $this->service()->recordSpellPropertyAdded(self::SPELL_ID, SpellProperty::MissImmune, self::COMBAT_LOG_PATH, Carbon::today());

        // Assert
        $this->assertNotNull($event);
        $this->assertSame(5, $event->fact_days);
        $this->assertSame(10, $event->subject_days);
    }

    #[Test]
    public function recordSpellPropertyAdded_givenGateDisabled_writesEventWithoutEvidence(): void
    {
        // Arrange
        config(['keystoneguru.combat_log_staleness.event_density_gate_enabled' => false]);

        // Act
        $event = $this->service()->recordSpellPropertyAdded(self::SPELL_ID, SpellProperty::MissImmune, self::COMBAT_LOG_PATH, Carbon::today());

        // Assert
        $this->assertNotNull($event);
        $this->assertDatabaseHas('combat_log_spell_events', [
            'spell_id'     => self::SPELL_ID,
            'event_type'   => CombatLogSpellEventType::PropertyChanged->value,
            'fact_days'    => null,
            'subject_days' => null,
        ], 'combatlog');
    }

    #[Test]
    public function recordSpellPropertyRemoved_givenReliableFactAndSubjectSeenInWindow_writesEventWithEvidence(): void
    {
        // Arrange - the spell is still seen every day, the property stopped 4 days ago after 6 of 10 days
        $this->observeSpell(SpellProperty::Aura, range(0, 9));
        $this->observeSpell(SpellProperty::MissImmune, range(4, 9));

        // Act
        $event = $this->service()->recordSpellPropertyRemoved(self::SPELL_ID, SpellProperty::MissImmune, $this->stalenessCutoff());

        // Assert
        $this->assertNotNull($event);
        $this->assertDatabaseHas('combat_log_spell_events', [
            'spell_id'        => self::SPELL_ID,
            'event_type'      => CombatLogSpellEventType::PropertyRemoved->value,
            'property'        => SpellProperty::MissImmune->value,
            'combat_log_path' => null,
            'fact_days'       => 6,
            'subject_days'    => 10,
        ], 'combatlog');
    }

    #[Test]
    public function recordSpellPropertyRemoved_givenSubjectNotSeenInWindow_writesNoEvent(): void
    {
        // Arrange - the whole spell went quiet, so the silence says nothing about the property
        $this->observeSpell(SpellProperty::Aura, range(4, 13));
        $this->observeSpell(SpellProperty::MissImmune, range(4, 13));

        // Act
        $event = $this->service()->recordSpellPropertyRemoved(self::SPELL_ID, SpellProperty::MissImmune, $this->stalenessCutoff());

        // Assert
        $this->assertNull($event);
        $this->assertSame(0, $this->spellEventCount(CombatLogSpellEventType::PropertyRemoved));
    }

    #[Test]
    public function recordSpellPropertyRemoved_givenRareFact_writesNoEvent(): void
    {
        // Arrange - seen on 2 of the spell's 10 days
        $this->observeSpell(SpellProperty::Aura, range(0, 9));
        $this->observeSpell(SpellProperty::MissImmune, [8, 9]);

        // Act
        $event = $this->service()->recordSpellPropertyRemoved(self::SPELL_ID, SpellProperty::MissImmune, $this->stalenessCutoff());

        // Assert
        $this->assertNull($event);
        $this->assertSame(0, $this->spellEventCount(CombatLogSpellEventType::PropertyRemoved));
    }

    #[Test]
    public function recordSpellPropertyRemoved_givenSubjectObservedOnFewerThanMinDays_writesNoEvent(): void
    {
        // Arrange - reliable (3 of 6) and recently seen, but only 6 days of history
        $this->observeSpell(SpellProperty::Aura, [0, 1, 2]);
        $this->observeSpell(SpellProperty::MissImmune, [4, 5, 6]);

        // Act
        $event = $this->service()->recordSpellPropertyRemoved(self::SPELL_ID, SpellProperty::MissImmune, $this->stalenessCutoff());

        // Assert
        $this->assertNull($event);
        $this->assertSame(0, $this->spellEventCount(CombatLogSpellEventType::PropertyRemoved));
    }

    #[Test]
    public function recordNpcCharacteristicAdded_givenNewFactOnLongObservedNpc_writesEventWithEvidence(): void
    {
        // Arrange
        $this->observeNpc(Characteristic::CHARACTERISTIC_STUN, range(1, 7));

        // Act
        $event = $this->service()->recordNpcCharacteristicAdded(self::NPC_ID, $this->characteristicId(Characteristic::CHARACTERISTIC_SILENCE), self::COMBAT_LOG_PATH, Carbon::today());

        // Assert
        $this->assertNotNull($event);
        $this->assertDatabaseHas('combat_log_npc_events', [
            'npc_id'          => self::NPC_ID,
            'event_type'      => CombatLogNpcEventType::CharacteristicAdded->value,
            'model_class'     => Characteristic::class,
            'model_id'        => $this->characteristicId(Characteristic::CHARACTERISTIC_SILENCE),
            'combat_log_path' => self::COMBAT_LOG_PATH,
            'fact_days'       => 0,
            'subject_days'    => 7,
        ], 'combatlog');
    }

    #[Test]
    public function recordNpcCharacteristicAdded_givenNpcNotObservedBeforeThatDay_writesNoEvent(): void
    {
        // Arrange - the NPC's own first observed day: every characteristic it has is new at once
        $this->observeNpc(Characteristic::CHARACTERISTIC_STUN, [0]);

        // Act
        $event = $this->service()->recordNpcCharacteristicAdded(self::NPC_ID, $this->characteristicId(Characteristic::CHARACTERISTIC_SILENCE), self::COMBAT_LOG_PATH, Carbon::today());

        // Assert
        $this->assertNull($event);
        $this->assertSame(0, $this->npcEventCount(CombatLogNpcEventType::CharacteristicAdded));
    }

    #[Test]
    public function recordNpcCharacteristicAdded_givenNewFactOnNpcObservedOnOneEarlierDay_writesEventWithEvidence(): void
    {
        // Arrange - one earlier day is enough history to tell a discovery from the NPC's own first sighting
        $this->observeNpc(Characteristic::CHARACTERISTIC_STUN, [1]);

        // Act
        $event = $this->service()->recordNpcCharacteristicAdded(self::NPC_ID, $this->characteristicId(Characteristic::CHARACTERISTIC_SILENCE), self::COMBAT_LOG_PATH, Carbon::today());

        // Assert
        $this->assertNotNull($event);
        $this->assertSame(0, $event->fact_days);
        $this->assertSame(1, $event->subject_days);
    }

    #[Test]
    public function recordNpcCharacteristicAdded_givenRareFactReturningOnYoungNpc_writesNoEvent(): void
    {
        // Arrange - a returning fact still needs the full NPC history, which the one-day allowance for brand new
        // facts does not grant it
        $this->observeNpc(Characteristic::CHARACTERISTIC_STUN, range(1, 3));
        $this->observeNpc(Characteristic::CHARACTERISTIC_SILENCE, [1]);

        // Act
        $event = $this->service()->recordNpcCharacteristicAdded(self::NPC_ID, $this->characteristicId(Characteristic::CHARACTERISTIC_SILENCE), self::COMBAT_LOG_PATH, Carbon::today());

        // Assert
        $this->assertNull($event);
        $this->assertSame(0, $this->npcEventCount(CombatLogNpcEventType::CharacteristicAdded));
    }

    #[Test]
    public function recordNpcCharacteristicAdded_givenTodaysObservationsWouldReachMinDays_writesNoEvent(): void
    {
        // Arrange - counting today would make 7 NPC days with the fact on 4 of them
        $this->observeNpc(Characteristic::CHARACTERISTIC_STUN, range(0, 6));
        $this->observeNpc(Characteristic::CHARACTERISTIC_SILENCE, range(0, 3));

        // Act
        $event = $this->service()->recordNpcCharacteristicAdded(self::NPC_ID, $this->characteristicId(Characteristic::CHARACTERISTIC_SILENCE), self::COMBAT_LOG_PATH, Carbon::today());

        // Assert
        $this->assertNull($event);
        $this->assertSame(0, $this->npcEventCount(CombatLogNpcEventType::CharacteristicAdded));
    }

    #[Test]
    public function recordNpcCharacteristicAdded_givenRareFactReturning_writesNoEvent(): void
    {
        // Arrange - seen on 2 of the NPC's 10 days
        $this->observeNpc(Characteristic::CHARACTERISTIC_STUN, range(1, 10));
        $this->observeNpc(Characteristic::CHARACTERISTIC_SILENCE, [9, 10]);

        // Act
        $event = $this->service()->recordNpcCharacteristicAdded(self::NPC_ID, $this->characteristicId(Characteristic::CHARACTERISTIC_SILENCE), self::COMBAT_LOG_PATH, Carbon::today());

        // Assert
        $this->assertNull($event);
        $this->assertSame(0, $this->npcEventCount(CombatLogNpcEventType::CharacteristicAdded));
    }

    #[Test]
    public function recordNpcCharacteristicRemoved_givenReliableFactAndNpcSeenInWindow_writesEventWithEvidence(): void
    {
        // Arrange
        $this->observeNpc(Characteristic::CHARACTERISTIC_STUN, range(0, 9));
        $this->observeNpc(Characteristic::CHARACTERISTIC_SILENCE, range(4, 9));

        // Act
        $event = $this->service()->recordNpcCharacteristicRemoved(self::NPC_ID, $this->characteristicId(Characteristic::CHARACTERISTIC_SILENCE), $this->stalenessCutoff());

        // Assert
        $this->assertNotNull($event);
        $this->assertDatabaseHas('combat_log_npc_events', [
            'npc_id'       => self::NPC_ID,
            'event_type'   => CombatLogNpcEventType::CharacteristicRemoved->value,
            'model_id'     => $this->characteristicId(Characteristic::CHARACTERISTIC_SILENCE),
            'fact_days'    => 6,
            'subject_days' => 10,
        ], 'combatlog');
    }

    #[Test]
    public function recordNpcCharacteristicRemoved_givenNpcNotSeenInWindow_writesNoEvent(): void
    {
        // Arrange
        $this->observeNpc(Characteristic::CHARACTERISTIC_STUN, range(4, 13));
        $this->observeNpc(Characteristic::CHARACTERISTIC_SILENCE, range(4, 13));

        // Act
        $event = $this->service()->recordNpcCharacteristicRemoved(self::NPC_ID, $this->characteristicId(Characteristic::CHARACTERISTIC_SILENCE), $this->stalenessCutoff());

        // Assert
        $this->assertNull($event);
        $this->assertSame(0, $this->npcEventCount(CombatLogNpcEventType::CharacteristicRemoved));
    }

    #[Test]
    public function recordNpcCharacteristicRemoved_givenRareFact_writesNoEvent(): void
    {
        // Arrange
        $this->observeNpc(Characteristic::CHARACTERISTIC_STUN, range(0, 9));
        $this->observeNpc(Characteristic::CHARACTERISTIC_SILENCE, [8, 9]);

        // Act
        $event = $this->service()->recordNpcCharacteristicRemoved(self::NPC_ID, $this->characteristicId(Characteristic::CHARACTERISTIC_SILENCE), $this->stalenessCutoff());

        // Assert
        $this->assertNull($event);
        $this->assertSame(0, $this->npcEventCount(CombatLogNpcEventType::CharacteristicRemoved));
    }

    private function service(): CombatLogActivityEventServiceInterface
    {
        return app(CombatLogActivityEventServiceInterface::class);
    }

    /**
     * The first day of a 3-day staleness window: observations on or after this are recent.
     */
    private function stalenessCutoff(): Carbon
    {
        return Carbon::today()->subDays(3);
    }

    private function characteristicId(string $characteristic): int
    {
        return Characteristic::ALL[$characteristic];
    }

    /**
     * @param array<int, int> $daysAgo
     */
    private function observeSpell(SpellProperty $property, array $daysAgo): void
    {
        foreach ($daysAgo as $days) {
            CombatLogSpellPropertyObservation::create([
                'spell_id'        => self::SPELL_ID,
                'property'        => $property,
                'observed_on'     => Carbon::today()->subDays($days)->toDateString(),
                'combat_log_path' => self::COMBAT_LOG_PATH,
            ]);
        }
    }

    /**
     * @param array<int, int> $daysAgo
     */
    private function observeNpc(string $characteristic, array $daysAgo): void
    {
        foreach ($daysAgo as $days) {
            CombatLogNpcCharacteristicObservation::create([
                'npc_id'            => self::NPC_ID,
                'characteristic_id' => $this->characteristicId($characteristic),
                'observed_on'       => Carbon::today()->subDays($days)->toDateString(),
                'combat_log_path'   => self::COMBAT_LOG_PATH,
            ]);
        }
    }

    private function spellEventCount(CombatLogSpellEventType $eventType): int
    {
        return CombatLogSpellEvent::query()
            ->where('spell_id', self::SPELL_ID)
            ->where('event_type', $eventType)
            ->count();
    }

    private function npcEventCount(CombatLogNpcEventType $eventType): int
    {
        return CombatLogNpcEvent::query()
            ->where('npc_id', self::NPC_ID)
            ->where('event_type', $eventType)
            ->count();
    }
}
