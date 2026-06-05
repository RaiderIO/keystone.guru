<?php

namespace Tests\Feature\App\Service\Compendium;

use App\Models\Characteristic;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\CombatLog\SpellProperty;
use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use App\Service\Compendium\NpcCompendiumService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Compendium')]
final class NpcCompendiumServiceActivityTest extends PublicTestCase
{
    private const int TEST_NPC_ID   = 9995099;
    private const int TEST_SPELL_ID = 9995099;

    private NpcCompendiumService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        CombatLogNpcEvent::where('npc_id', self::TEST_NPC_ID)->delete();
        CombatLogSpellEvent::where('spell_id', self::TEST_SPELL_ID)->delete();
        Spell::where('id', self::TEST_SPELL_ID)->delete();

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
}
