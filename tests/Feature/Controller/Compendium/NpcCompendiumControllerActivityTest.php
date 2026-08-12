<?php

namespace Tests\Feature\Controller\Compendium;

use App\Features\NpcCompendium;
use App\Models\Characteristic;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\CombatLog\SpellProperty;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Models\User;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Compendium')]
final class NpcCompendiumControllerActivityTest extends PublicTestCase
{
    private const int TEST_SPELL_ID = 9995098;

    private Dungeon $dungeon;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::findOrFail(1));
        Feature::define(NpcCompendium::class, true);

        // Clean up sentinel rows a previously aborted run may have left behind
        CombatLogSpellEvent::query()->where('spell_id', self::TEST_SPELL_ID)->delete();
        NpcSpell::query()->where('spell_id', self::TEST_SPELL_ID)->delete();
        Spell::query()->where('id', self::TEST_SPELL_ID)->delete();

        // Must be a dungeon of the CURRENT season - that is what the controller scopes to, and it
        // redirects away from anything else. Taking the highest season id instead breaks as soon as an
        // upcoming season is seeded ahead of its start date, which is exactly what happens every time a
        // new season's mapping is prepared in advance.
        $this->dungeon = app(SeasonServiceInterface::class)->getCurrentSeason()->dungeons()->first();
    }

    #[Test]
    public function activityIndex_givenFeatureDisabled_returnsNotFound(): void
    {
        // Arrange
        Feature::define(NpcCompendium::class, false);

        // Act
        $response = $this->get(route('compendium.activity.index'));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function activityIndex_givenFeatureEnabled_redirectsToDungeon(): void
    {
        // Act
        $response = $this->get(route('compendium.activity.index'));

        // Assert
        $response->assertRedirect();
    }

    #[Test]
    public function activity_givenFeatureEnabled_returnsOk(): void
    {
        // Act
        $response = $this->get(route('compendium.activity', $this->dungeon));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function activity_givenFeatureEnabledNoAuth_returnsOk(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->get(route('compendium.activity', $this->dungeon));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function activityDay_givenValidDate_returnsOk(): void
    {
        // Act
        $response = $this->get(route('compendium.activity.day', ['dungeon' => $this->dungeon, 'date' => '2025-01-15']));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function activity_givenMoreEventsThanTheDayLimit_rendersCollapsedMoreLink(): void
    {
        // Arrange - 17 events on one day; the activity index caps each day at 15 rows. The index
        // paginates dates newest-first, so the date must sort ahead of any other data (local dev
        // DBs can hold demo events on recent dates) or the day under test never renders on page 1.
        $uniqueDate       = '2027-01-01';
        $npcId            = DB::table('npc_dungeons')->where('dungeon_id', $this->dungeon->id)->value('npc_id');
        $characteristicId = Characteristic::query()->orderBy('id')->value('id');

        // A previously aborted run's leftovers on this date would skew the overflow count
        CombatLogNpcEvent::query()->where('npc_id', $npcId)->whereDate('created_at', $uniqueDate)->delete();

        $eventIds = [];
        for ($i = 0; $i < 17; $i++) {
            // insert(), not create(): created_at is not fillable so create() silently drops it
            $eventIds[] = CombatLogNpcEvent::query()->insertGetId([
                'npc_id'      => $npcId,
                'event_type'  => CombatLogNpcEventType::CharacteristicAdded->value,
                'model_class' => Characteristic::class,
                'model_id'    => $characteristicId,
                'created_at'  => $uniqueDate . ' 12:00:00',
            ]);
        }

        try {
            // Act
            $response = $this->get(route('compendium.activity', $this->dungeon));

            // Assert - the two overflow rows collapse into a link to the day page
            $response->assertOk();
            $response->assertSee(__('view_compendium.event.more', ['count' => 2]));
            $response->assertSee(route('compendium.activity.day', ['dungeon' => $this->dungeon, 'date' => $uniqueDate]));
        } finally {
            CombatLogNpcEvent::query()->whereIn('id', $eventIds)->delete();
        }
    }

    #[Test]
    public function activityDay_givenSpellEventWithSubjectShown_rendersSpellLinkAndNoSubjectDescription(): void
    {
        // Arrange - a classic-era spell assigned to a dungeon NPC, with a counter event on a unique day
        $uniqueDate = '2025-06-16';
        $npcId      = DB::table('npc_dungeons')->where('dungeon_id', $this->dungeon->id)->value('npc_id');

        $spell = Spell::create([
            'id'              => self::TEST_SPELL_ID,
            'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA],
            'dispel_type'     => '',
            'mechanic'        => '',
            'icon_name'       => '',
            'name'            => 'TestActivityDaySpell',
            'schools_mask'    => 1,
            'miss_types_mask' => 0,
            'aura'            => false,
            'debuff'          => false,
            'cast_time'       => 0,
            'duration'        => 0,
            'selectable'      => false,
            'hidden_on_map'   => false,
            'fetched_data_at' => now(),
        ]);
        NpcSpell::create(['npc_id' => $npcId, 'spell_id' => self::TEST_SPELL_ID]);

        // insert(), not create(): created_at is not fillable so create() silently drops it
        $eventId = CombatLogSpellEvent::query()->insertGetId([
            'spell_id'   => self::TEST_SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyChanged->value,
            'property'   => SpellProperty::CounterVanish->value,
            'created_at' => $uniqueDate . ' 12:00:00',
        ]);

        try {
            // Act
            $response = $this->get(route('compendium.activity.day', ['dungeon' => $this->dungeon, 'date' => $uniqueDate]));

            // Assert - the spell renders as the subject link (with game-version-aware Wowhead tooltip
            // data) and the description switches to the subject-less wording
            $response->assertOk();
            $response->assertSee(route('spell.compendium.show', $spell));
            $response->assertSee('data-wowhead="spell=' . self::TEST_SPELL_ID . '&amp;domain=classic"', false);
            $response->assertSee(__('view_compendium.event.counter_added_no_subject', [
                'spell'    => 'TestActivityDaySpell',
                'property' => __('spellcounters.vanish'),
            ]));
            $response->assertDontSee(__('view_compendium.event.counter_added', [
                'spell'    => 'TestActivityDaySpell',
                'property' => __('spellcounters.vanish'),
            ]));
        } finally {
            CombatLogSpellEvent::query()->where('id', $eventId)->delete();
            NpcSpell::query()->where('spell_id', self::TEST_SPELL_ID)->delete();
            Spell::query()->where('id', self::TEST_SPELL_ID)->delete();
        }
    }

    #[Test]
    public function activityDay_givenAddedNpcAndSpellEvents_rendersSameGlyph(): void
    {
        // Arrange - a "something started applying" event from each of the NPC and spell tables,
        // on the same unique day, must render with the same icon/color regardless of which table
        // the event came from
        $uniqueDate       = '2025-07-21';
        $npcId            = DB::table('npc_dungeons')->where('dungeon_id', $this->dungeon->id)->value('npc_id');
        $characteristicId = Characteristic::query()->orderBy('id')->value('id');

        $npcEventId = CombatLogNpcEvent::query()->insertGetId([
            'npc_id'      => $npcId,
            'event_type'  => CombatLogNpcEventType::CharacteristicAdded->value,
            'model_class' => Characteristic::class,
            'model_id'    => $characteristicId,
            'created_at'  => $uniqueDate . ' 12:00:00',
        ]);
        $spellEventId = CombatLogSpellEvent::query()->insertGetId([
            'spell_id'   => self::TEST_SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyChanged->value,
            'property'   => SpellProperty::Aura->value,
            'created_at' => $uniqueDate . ' 12:01:00',
        ]);

        try {
            // Act
            $response = $this->get(route('compendium.activity.day', ['dungeon' => $this->dungeon, 'date' => $uniqueDate]));

            // Assert - both rows use the "added" glyph (fa-plus, text-success) and none of them use
            // the old spell-only fa-arrow-up/text-warning glyph
            $response->assertOk();
            $response->assertSeeInOrder([
                '<span class="compendium_log_icon text-success">',
                '<i class="fas fa-plus"></i>',
            ], false);
            $response->assertDontSee('fa-arrow-up');
        } finally {
            CombatLogNpcEvent::query()->where('id', $npcEventId)->delete();
            CombatLogSpellEvent::query()->where('id', $spellEventId)->delete();
        }
    }

    #[Test]
    public function activityDay_givenRemovedNpcAndSpellEvents_rendersSameGlyph(): void
    {
        // Arrange - a "something stopped applying" event from each of the NPC and spell tables, on
        // the same unique day, must render with the same icon/color regardless of which table the
        // event came from
        $uniqueDate       = '2025-07-22';
        $npcId            = DB::table('npc_dungeons')->where('dungeon_id', $this->dungeon->id)->value('npc_id');
        $characteristicId = Characteristic::query()->orderBy('id')->value('id');

        $npcEventId = CombatLogNpcEvent::query()->insertGetId([
            'npc_id'      => $npcId,
            'event_type'  => CombatLogNpcEventType::CharacteristicRemoved->value,
            'model_class' => Characteristic::class,
            'model_id'    => $characteristicId,
            'created_at'  => $uniqueDate . ' 12:00:00',
        ]);
        $spellEventId = CombatLogSpellEvent::query()->insertGetId([
            'spell_id'   => self::TEST_SPELL_ID,
            'event_type' => CombatLogSpellEventType::PropertyRemoved->value,
            'property'   => SpellProperty::Aura->value,
            'created_at' => $uniqueDate . ' 12:01:00',
        ]);

        try {
            // Act
            $response = $this->get(route('compendium.activity.day', ['dungeon' => $this->dungeon, 'date' => $uniqueDate]));

            // Assert - both rows use the same "removed" glyph (fa-minus, text-danger)
            $response->assertOk();
            $response->assertSeeInOrder([
                '<span class="compendium_log_icon text-danger">',
                '<i class="fas fa-minus"></i>',
            ], false);
            $response->assertDontSee('fa-times');
        } finally {
            CombatLogNpcEvent::query()->where('id', $npcEventId)->delete();
            CombatLogSpellEvent::query()->where('id', $spellEventId)->delete();
        }
    }

    #[Test]
    public function activityDay_givenInvalidDateFormat_returnsNotFound(): void
    {
        // Act
        $response = $this->get(sprintf('/compendium/activity/%s/not-a-date', $this->dungeon->slug));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function activityDay_givenFeatureDisabled_returnsNotFound(): void
    {
        // Arrange
        Feature::define(NpcCompendium::class, false);

        // Act
        $response = $this->get(route('compendium.activity.day', ['dungeon' => $this->dungeon, 'date' => '2025-01-15']));

        // Assert
        $response->assertNotFound();
    }
}
