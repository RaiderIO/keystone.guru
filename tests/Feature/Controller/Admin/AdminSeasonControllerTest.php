<?php

namespace Tests\Feature\Controller\Admin;

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
            $this->assertEqualsCanonicalizing(
                $dungeons->pluck('id')->toArray(),
                $season->seasonDungeons()->pluck('dungeon_id')->toArray(),
            );
        } finally {
            $season?->seasonDungeons()->delete();
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
            $season->seasonDungeons()->delete();
            $season->delete();
        }
    }
}
