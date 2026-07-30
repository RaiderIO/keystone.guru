<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Affix;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Laratrust\Role;
use App\Models\Season;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
final class AdminSeasonControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function get_asAdmin_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.seasons'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function get_asNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));
        $this->be($user);

        // Act
        $response = $this->get(route('admin.seasons'));

        // Assert
        $response->assertForbidden();
    }

    #[Test]
    public function edit_givenExistingSeason_returnsOk(): void
    {
        // Arrange
        $season = Season::query()->firstOrFail();

        // Act
        $response = $this->get(route('admin.season.edit', $season));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function create_givenNoSeason_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.season.new'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function edit_givenSeasonWithNoSeasonalAffix_preselectsNoneOption(): void
    {
        // Arrange
        $season = Season::create([
            'expansion_id'            => Expansion::query()->firstOrFail()->id,
            'seasonal_affix_id'       => null,
            'index'                   => 994,
            'start'                   => '2026-01-01 00:00:00',
            'presets'                 => 0,
            'affix_group_count'       => 4,
            'start_affix_group_index' => 0,
            'key_level_min'           => 2,
            'key_level_max'           => 30,
        ]);

        try {
            // Act
            $response = $this->get(route('admin.season.edit', $season));

            // Assert - a real bug: without this, the browser silently preselects the
            // alphabetically-first seasonal affix instead of leaving the field unset.
            $response->assertOk();
            $response->assertSee('value="-1" selected="selected"', false);
        } finally {
            $season->delete();
        }
    }

    #[Test]
    public function savenew_givenRealSeasonalAffixId_persistsAndPreselectsIt(): void
    {
        // Arrange
        $expansion = Expansion::query()->firstOrFail();
        $affix     = Affix::whereIn('key', Affix::SEASONAL_AFFIXES)->firstOrFail();
        $season    = null;

        try {
            // Act
            $response = $this->post(route('admin.season.savenew'), [
                'expansion_id'            => $expansion->id,
                'seasonal_affix_id'       => $affix->affix_id,
                'index'                   => 993,
                'start'                   => '2026-01-01 00:00:00',
                'affix_group_count'       => 4,
                'start_affix_group_index' => 0,
                'key_level_min'           => 2,
                'key_level_max'           => 30,
            ]);

            // Assert
            $season = Season::query()->where('index', 993)->first();
            $this->assertNotNull($season);
            $response->assertRedirect(route('admin.season.edit', $season));
            $this->assertSame($affix->affix_id, $season->seasonal_affix_id);

            $editResponse = $this->get(route('admin.season.edit', $season));
            $editResponse->assertSee(sprintf('value="%d" selected="selected"', $affix->affix_id), false);
        } finally {
            $season?->delete();
        }
    }

    #[Test]
    public function savenew_givenValidDataWithDungeons_createsSeasonAndSyncsDungeons(): void
    {
        // Arrange
        $expansion = Expansion::query()->firstOrFail();
        $dungeons  = Dungeon::query()->limit(2)->get();
        $season    = null;

        try {
            // Act
            $response = $this->post(route('admin.season.savenew'), [
                'expansion_id'            => $expansion->id,
                'seasonal_affix_id'       => -1,
                'index'                   => 999,
                'start'                   => '2026-01-01 00:00:00',
                'affix_group_count'       => 10,
                'start_affix_group_index' => 0,
                'key_level_min'           => 2,
                'key_level_max'           => 30,
                'dungeon_ids'             => $dungeons->pluck('id')->toArray(),
            ]);

            // Assert
            $season = Season::query()->where('index', 999)->first();
            $this->assertNotNull($season);
            $response->assertRedirect(route('admin.season.edit', $season));
            $this->assertSame(0, $season->presets);
            $this->assertNull($season->seasonal_affix_id, '-1 (the "none selected" option) must be normalized to null');
            $this->assertEqualsCanonicalizing(
                $dungeons->pluck('id')->toArray(),
                $season->seasonDungeons()->pluck('dungeon_id')->toArray(),
            );
        } finally {
            $season?->syncDungeons([]);
            $season?->delete();
        }
    }

    #[Test]
    public function savenew_givenStartAffixGroupIndexNotLessThanCount_returnsValidationError(): void
    {
        // Arrange
        $expansion = Expansion::query()->firstOrFail();

        // Act
        $response = $this->post(route('admin.season.savenew'), [
            'expansion_id'            => $expansion->id,
            'seasonal_affix_id'       => -1,
            'index'                   => 998,
            'start'                   => '2026-01-01 00:00:00',
            'affix_group_count'       => 4,
            'start_affix_group_index' => 4,
            'key_level_min'           => 2,
            'key_level_max'           => 30,
        ]);

        // Assert
        $response->assertSessionHasErrors('start_affix_group_index');
        $this->assertFalse(Season::query()->where('index', 998)->exists());
    }

    #[Test]
    public function update_givenNewDungeonSet_replacesSeasonDungeons(): void
    {
        // Arrange
        $expansion = Expansion::query()->firstOrFail();
        $dungeons  = Dungeon::query()->limit(3)->get();

        $season = Season::create([
            'expansion_id'            => $expansion->id,
            'index'                   => 997,
            'start'                   => '2026-01-01 00:00:00',
            'presets'                 => 0,
            'affix_group_count'       => 4,
            'start_affix_group_index' => 0,
            'key_level_min'           => 2,
            'key_level_max'           => 30,
        ]);
        $season->syncDungeons([$dungeons->first()->id]);

        try {
            $newDungeonIds = $dungeons->skip(1)->pluck('id')->toArray();

            // Act
            $response = $this->patch(route('admin.season.update', $season), [
                'expansion_id'            => $expansion->id,
                'seasonal_affix_id'       => -1,
                'index'                   => 997,
                'start'                   => '2026-01-01 00:00:00',
                'affix_group_count'       => 4,
                'start_affix_group_index' => 0,
                'key_level_min'           => 2,
                'key_level_max'           => 30,
                'dungeon_ids'             => $newDungeonIds,
            ]);

            // Assert
            $response->assertOk();
            $this->assertEqualsCanonicalizing(
                $newDungeonIds,
                $season->seasonDungeons()->pluck('dungeon_id')->toArray(),
            );
        } finally {
            $season->syncDungeons([]);
            $season->delete();
        }
    }
}
