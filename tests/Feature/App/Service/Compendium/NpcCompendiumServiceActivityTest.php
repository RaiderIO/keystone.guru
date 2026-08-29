<?php

namespace Tests\Feature\App\Service\Compendium;

use App\Models\Characteristic;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\CombatLog\SpellProperty;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Service\Compendium\NpcCompendiumService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Compendium')]
final class NpcCompendiumServiceActivityTest extends PublicTestCase
{
    private const int TEST_NPC_ID           = 9995099;
    private const int TEST_SPELL_ID         = 9995099;
    private const int TEST_VISIBLE_SPELL_ID = 9995098;

    private NpcCompendiumService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        CombatLogNpcEvent::where('npc_id', self::TEST_NPC_ID)->delete();
        CombatLogSpellEvent::where('spell_id', self::TEST_SPELL_ID)->delete();
        CombatLogSpellEvent::where('spell_id', self::TEST_VISIBLE_SPELL_ID)->delete();
        NpcSpell::where('npc_id', self::TEST_NPC_ID)->delete();
        Spell::whereIn('id', [self::TEST_SPELL_ID, self::TEST_VISIBLE_SPELL_ID])->delete();
        Npc::where('id', self::TEST_NPC_ID)->delete();

        $this->service = new NpcCompendiumService();
    }

    #[Test]
    public function getActivityDates_givenNpcEventOnUniqueDate_returnsThatDateInResults(): void
    {
        // Arrange — create an event on a uniquely identifiable past date
        $uniqueDate = '2000-01-01';
        $npcEvent   = CombatLogNpcEvent::create([
            'npc_id'      => self::TEST_NPC_ID,
            'event_type'  => CombatLogNpcEventType::CharacteristicAdded->value,
            'model_class' => Characteristic::class,
            'model_id'    => 1,
        ]);
        CombatLogNpcEvent::where('id', $npcEvent->id)->update(['created_at' => $uniqueDate . ' 12:00:00']);

        try {
            // Act — fetch all dates at once using a large perPage
            $paginator = $this->service->getActivityDates(PHP_INT_MAX);

            // Assert — the unique date is somewhere in the results
            $this->assertTrue(
                collect($paginator->items())->contains($uniqueDate),
                sprintf('Date %s was not found in getActivityDates() results', $uniqueDate),
            );
        } finally {
            $npcEvent->delete();
        }
    }

    #[Test]
    public function getEventsForDate_givenNpcAndSpellEvents_returnsMergedCollection(): void
    {
        // Arrange
        $today            = Carbon::today();
        $characteristicId = Characteristic::orderBy('id')->value('id');
        $gameVersion      = GameVersion::first();

        $spell = Spell::create([
            'id'              => self::TEST_SPELL_ID,
            'game_version_id' => $gameVersion->id,
            'dispel_type'     => '',
            'mechanic'        => '',
            'icon_name'       => '',
            'name'            => 'TestActivitySpell',
            'schools_mask'    => 1,
            'miss_types_mask' => 0,
            'aura'            => false,
            'debuff'          => false,
            'cast_time'       => 0,
            'duration'        => 0,
            'selectable'      => false,
            'hidden_on_map'   => false,
            'fetched_data_at' => $today,
        ]);

        $npcEvent = CombatLogNpcEvent::create([
            'npc_id'      => self::TEST_NPC_ID,
            'event_type'  => CombatLogNpcEventType::CharacteristicAdded->value,
            'model_class' => Characteristic::class,
            'model_id'    => $characteristicId,
        ]);

        $spellEvent = CombatLogSpellEvent::create([
            'spell_id'   => self::TEST_SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyChanged->value,
            'property'   => SpellProperty::Aura->value,
        ]);

        try {
            // Act
            $events = $this->service->getEventsForDate($today);

            // Assert — both the NPC event and spell event are present
            $npcEventIds   = $events->whereInstanceOf(CombatLogNpcEvent::class)->pluck('id');
            $spellEventIds = $events->whereInstanceOf(CombatLogSpellEvent::class)->pluck('id');

            $this->assertTrue($npcEventIds->contains($npcEvent->id));
            $this->assertTrue($spellEventIds->contains($spellEvent->id));
        } finally {
            $npcEvent->delete();
            $spellEvent->delete();
            $spell->delete();
        }
    }

    #[Test]
    public function getEventsForDate_givenHiddenSpellNpcEvent_filtersItOut(): void
    {
        // Arrange
        $today       = Carbon::today();
        $gameVersion = GameVersion::first();

        $hiddenSpell = Spell::create([
            'id'              => self::TEST_SPELL_ID,
            'game_version_id' => $gameVersion->id,
            'dispel_type'     => '',
            'mechanic'        => '',
            'icon_name'       => '',
            'name'            => 'TestHiddenSpell',
            'schools_mask'    => 1,
            'miss_types_mask' => 0,
            'aura'            => false,
            'debuff'          => false,
            'cast_time'       => 0,
            'duration'        => 0,
            'selectable'      => false,
            'hidden_on_map'   => true,
            'fetched_data_at' => $today,
        ]);

        $npcEvent = CombatLogNpcEvent::create([
            'npc_id'      => self::TEST_NPC_ID,
            'event_type'  => CombatLogNpcEventType::SpellAssigned->value,
            'model_class' => Spell::class,
            'model_id'    => self::TEST_SPELL_ID,
        ]);

        try {
            // Act
            $events = $this->service->getEventsForDate($today);

            // Assert — the NPC event referencing the hidden spell is filtered out
            $this->assertFalse(
                $events->whereInstanceOf(CombatLogNpcEvent::class)->pluck('id')->contains($npcEvent->id),
            );
        } finally {
            $npcEvent->delete();
            $hiddenSpell->delete();
        }
    }

    #[Test]
    public function getEventsForDate_givenHiddenSpellEvent_filtersItOut(): void
    {
        // Arrange — a PropertyChanged event on a hidden spell used to render on the activity page
        // because only the NPC events were checked for hidden_on_map (#4356).
        $today       = Carbon::today();
        $gameVersion = GameVersion::first();

        $hiddenSpell = $this->createTestSpell($gameVersion->id, 'TestHiddenSpellEvent', true);

        $spellEvent = CombatLogSpellEvent::create([
            'spell_id'   => self::TEST_SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyChanged->value,
            'property'   => SpellProperty::Aura->value,
        ]);

        try {
            // Act
            $events = $this->service->getEventsForDate($today);

            // Assert
            $this->assertFalse(
                $events->whereInstanceOf(CombatLogSpellEvent::class)->pluck('id')->contains($spellEvent->id),
            );
        } finally {
            $spellEvent->delete();
            $hiddenSpell->delete();
        }
    }

    #[Test]
    public function getEventsForDate_givenVisibleSpellEvent_returnsIt(): void
    {
        // Arrange
        $today       = Carbon::today();
        $gameVersion = GameVersion::first();

        $visibleSpell = $this->createTestSpell($gameVersion->id, 'TestVisibleSpellEvent', false);

        $spellEvent = CombatLogSpellEvent::create([
            'spell_id'   => self::TEST_SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyChanged->value,
            'property'   => SpellProperty::Aura->value,
        ]);

        try {
            // Act
            $events = $this->service->getEventsForDate($today);

            // Assert
            $this->assertTrue(
                $events->whereInstanceOf(CombatLogSpellEvent::class)->pluck('id')->contains($spellEvent->id),
            );
        } finally {
            $spellEvent->delete();
            $visibleSpell->delete();
        }
    }

    #[Test]
    public function buildEventFeed_givenHiddenSpellEvent_filtersItOut(): void
    {
        // Arrange — same asymmetry as getEventsForDate(), on the per-NPC feed (#4356).
        $gameVersion = GameVersion::first();

        $npc = $this->createTestNpc();
        $this->createTestSpell($gameVersion->id, 'TestHiddenFeedSpell', true);
        NpcSpell::create([
            'npc_id'   => self::TEST_NPC_ID,
            'spell_id' => self::TEST_SPELL_ID,
        ]);
        $spellEvent = CombatLogSpellEvent::create([
            'spell_id'   => self::TEST_SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyChanged->value,
            'property'   => SpellProperty::Aura->value,
        ]);

        try {
            // Act
            $events = $this->service->buildEventFeed($npc->load('npcSpells'));

            // Assert
            $this->assertFalse(
                $events->whereInstanceOf(CombatLogSpellEvent::class)->pluck('id')->contains($spellEvent->id),
            );
        } finally {
            $spellEvent->delete();
            NpcSpell::where('npc_id', self::TEST_NPC_ID)->delete();
            Spell::where('id', self::TEST_SPELL_ID)->delete();
            Npc::where('id', self::TEST_NPC_ID)->delete();
        }
    }

    #[Test]
    public function buildEventFeed_givenVisibleSpellEvent_returnsIt(): void
    {
        // Arrange
        $gameVersion = GameVersion::first();

        $npc = $this->createTestNpc();
        $this->createTestSpell($gameVersion->id, 'TestVisibleFeedSpell', false);
        NpcSpell::create([
            'npc_id'   => self::TEST_NPC_ID,
            'spell_id' => self::TEST_SPELL_ID,
        ]);
        $spellEvent = CombatLogSpellEvent::create([
            'spell_id'   => self::TEST_SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyChanged->value,
            'property'   => SpellProperty::Aura->value,
        ]);

        try {
            // Act
            $events = $this->service->buildEventFeed($npc->load('npcSpells'));

            // Assert
            $this->assertTrue(
                $events->whereInstanceOf(CombatLogSpellEvent::class)->pluck('id')->contains($spellEvent->id),
            );
        } finally {
            $spellEvent->delete();
            NpcSpell::where('npc_id', self::TEST_NPC_ID)->delete();
            Spell::where('id', self::TEST_SPELL_ID)->delete();
            Npc::where('id', self::TEST_NPC_ID)->delete();
        }
    }

    #[Test]
    public function getActivityDates_givenDateWithOnlyHiddenSpellEvents_omitsThatDate(): void
    {
        // Arrange — before #4356 the date survived the (post-hoc, events-only) hidden filter and
        // paginated as a day that then rendered zero events.
        $uniqueDate  = '2000-01-02';
        $gameVersion = GameVersion::first();

        $hiddenSpell = $this->createTestSpell($gameVersion->id, 'TestHiddenOnlyDaySpell', true);

        $spellEvent = CombatLogSpellEvent::create([
            'spell_id'   => self::TEST_SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyChanged->value,
            'property'   => SpellProperty::Aura->value,
        ]);
        CombatLogSpellEvent::where('id', $spellEvent->id)->update(['created_at' => $uniqueDate . ' 12:00:00']);

        try {
            // Act
            $paginator = $this->service->getActivityDates(PHP_INT_MAX);

            // Assert
            $this->assertFalse(
                collect($paginator->items())->contains($uniqueDate),
                sprintf('Date %s should not appear - its only event is on a hidden spell', $uniqueDate),
            );
        } finally {
            $spellEvent->delete();
            $hiddenSpell->delete();
        }
    }

    #[Test]
    public function getActivityDates_givenDateWithOnlyHiddenSpellNpcEvents_omitsThatDate(): void
    {
        // Arrange — the same for the NPC side, where a SpellAssigned points at a hidden spell.
        $uniqueDate  = '2000-01-03';
        $gameVersion = GameVersion::first();

        $hiddenSpell = $this->createTestSpell($gameVersion->id, 'TestHiddenOnlyDayNpcSpell', true);

        $npcEvent = CombatLogNpcEvent::create([
            'npc_id'      => self::TEST_NPC_ID,
            'event_type'  => CombatLogNpcEventType::SpellAssigned->value,
            'model_class' => Spell::class,
            'model_id'    => self::TEST_SPELL_ID,
        ]);
        CombatLogNpcEvent::where('id', $npcEvent->id)->update(['created_at' => $uniqueDate . ' 12:00:00']);

        try {
            // Act
            $paginator = $this->service->getActivityDates(PHP_INT_MAX);

            // Assert
            $this->assertFalse(
                collect($paginator->items())->contains($uniqueDate),
                sprintf('Date %s should not appear - its only event points at a hidden spell', $uniqueDate),
            );
        } finally {
            $npcEvent->delete();
            $hiddenSpell->delete();
        }
    }

    #[Test]
    public function buildEventFeed_givenBurstOfHiddenSpellEvents_doesNotCrowdOutVisibleHistory(): void
    {
        // Arrange — 50 newer hidden-spell events plus one older visible one. Filtering after the
        // query's limit(50) would return only the hidden burst and drop the visible event entirely;
        // filtering in the query keeps it.
        $gameVersion = GameVersion::first();

        $npc = $this->createTestNpc();
        $this->createTestSpell($gameVersion->id, 'TestBurstHiddenSpell', true);
        $this->createTestSpell($gameVersion->id, 'TestBurstVisibleSpell', false, self::TEST_VISIBLE_SPELL_ID);
        NpcSpell::create(['npc_id' => self::TEST_NPC_ID, 'spell_id' => self::TEST_SPELL_ID]);
        NpcSpell::create(['npc_id' => self::TEST_NPC_ID, 'spell_id' => self::TEST_VISIBLE_SPELL_ID]);

        $visibleEvent = CombatLogSpellEvent::create([
            'spell_id'   => self::TEST_VISIBLE_SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyChanged->value,
            'property'   => SpellProperty::Aura->value,
        ]);
        CombatLogSpellEvent::where('id', $visibleEvent->id)->update(['created_at' => '2000-01-04 12:00:00']);

        for ($i = 0; $i < 50; $i++) {
            $hiddenEvent = CombatLogSpellEvent::create([
                'spell_id'   => self::TEST_SPELL_ID,
                'event_type' => CombatLogSpellEventType::PropertyChanged->value,
                'property'   => SpellProperty::Aura->value,
            ]);
            CombatLogSpellEvent::where('id', $hiddenEvent->id)->update(['created_at' => '2000-01-05 12:00:00']);
        }

        try {
            // Act
            $events = $this->service->buildEventFeed($npc->load('npcSpells'));

            // Assert — the visible event survived the burst, and no hidden event came through
            $spellEventIds = $events->whereInstanceOf(CombatLogSpellEvent::class)->pluck('id');
            $this->assertTrue($spellEventIds->contains($visibleEvent->id));
            $this->assertSame(
                0,
                $events->whereInstanceOf(CombatLogSpellEvent::class)
                    ->where('spell_id', self::TEST_SPELL_ID)
                    ->count(),
            );
        } finally {
            CombatLogSpellEvent::whereIn('spell_id', [self::TEST_SPELL_ID, self::TEST_VISIBLE_SPELL_ID])->delete();
            NpcSpell::where('npc_id', self::TEST_NPC_ID)->delete();
            Spell::whereIn('id', [self::TEST_SPELL_ID, self::TEST_VISIBLE_SPELL_ID])->delete();
            Npc::where('id', self::TEST_NPC_ID)->delete();
        }
    }

    private function createTestNpc(): Npc
    {
        return Npc::create([
            'id'                => self::TEST_NPC_ID,
            'classification_id' => 1,
            'npc_type_id'       => 1,
            'npc_class_id'      => 1,
            'display_id'        => null,
            'name'              => 'Test Compendium NPC',
            'aggressiveness'    => Npc::AGGRESSIVENESS_AGGRESSIVE,
            'dangerous'         => 0,
            'truesight'         => 0,
        ]);
    }

    private function createTestSpell(int $gameVersionId, string $name, bool $hiddenOnMap, int $spellId = self::TEST_SPELL_ID): Spell
    {
        return Spell::create([
            'id'              => $spellId,
            'game_version_id' => $gameVersionId,
            'dispel_type'     => '',
            'mechanic'        => '',
            'icon_name'       => '',
            'name'            => $name,
            'schools_mask'    => 1,
            'miss_types_mask' => 0,
            'aura'            => false,
            'debuff'          => false,
            'cast_time'       => 0,
            'duration'        => 0,
            'selectable'      => false,
            'hidden_on_map'   => $hiddenOnMap,
            'fetched_data_at' => Carbon::today(),
        ]);
    }
}
