<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Affix;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Laratrust\Role;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
    public function get_asAdmin_ordersSeasonsByIdDescending(): void
    {
        // Arrange
        $ids = Season::query()->orderByDesc('id')->pluck('id')->toArray();

        // Act
        $response = $this->get(route('admin.seasons'));

        // Assert
        $response->assertOk();
        $this->assertSame($ids, $response->viewData('models')->pluck('id')->toArray());
    }

    #[Test]
    public function get_asNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));
        $this->be($user);

        try {
            // Act
            $response = $this->get(route('admin.seasons'));

            // Assert
            $response->assertForbidden();
        } finally {
            $user->delete();
        }
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
    public function create_givenAllDeclaredSeasonIdsAlreadyUsed_showsNoAvailableIdsWarning(): void
    {
        // Arrange - every Season::SEASON_* constant already has a seeded row in the fixture DB, so
        // this is the only reachable state for the create page today.
        $this->assertEmpty(Season::getAvailableIds(), 'Fixture DB assumption changed - add a covering test for the id-select branch.');

        // Act
        $response = $this->get(route('admin.season.new'));

        // Assert
        $response->assertOk();
        $response->assertSee(__('view_admin.season.edit.no_available_ids'));
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
    public function update_givenRealSeasonalAffixId_persistsAndPreselectsIt(): void
    {
        // Arrange
        $expansion = Expansion::query()->firstOrFail();
        $affix     = Affix::whereIn('key', Affix::SEASONAL_AFFIXES)->firstOrFail();
        $season    = Season::create([
            'expansion_id'            => $expansion->id,
            'seasonal_affix_id'       => null,
            'index'                   => 993,
            'start'                   => '2026-01-01 00:00:00',
            'presets'                 => 0,
            'affix_group_count'       => 4,
            'start_affix_group_index' => 0,
            'key_level_min'           => 2,
            'key_level_max'           => 30,
        ]);

        try {
            // Act
            $response = $this->patch(route('admin.season.update', $season), [
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
            $response->assertOk();
            $this->assertSame($affix->affix_id, $season->fresh()->seasonal_affix_id);
            $response->assertSee(sprintf('value="%d" selected="selected"', $affix->affix_id), false);
        } finally {
            $season->delete();
        }
    }

    #[Test]
    public function update_givenValidDataWithDungeons_syncsDungeonsAndDefaultsPresetsAndSeasonalAffixId(): void
    {
        // Arrange
        $expansion = Expansion::query()->firstOrFail();
        $dungeons  = Dungeon::query()->limit(2)->get();
        $season    = Season::create([
            'expansion_id'            => $expansion->id,
            'seasonal_affix_id'       => null,
            'index'                   => 999,
            'start'                   => '2026-01-01 00:00:00',
            'presets'                 => 5,
            'affix_group_count'       => 10,
            'start_affix_group_index' => 0,
            'key_level_min'           => 2,
            'key_level_max'           => 30,
        ]);

        try {
            // Act - presets is omitted so the controller must default it back to 0
            $response = $this->patch(route('admin.season.update', $season), [
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
            $response->assertOk();
            $season->refresh();
            $this->assertSame(0, $season->presets);
            $this->assertNull($season->seasonal_affix_id, '-1 (the "none selected" option) must be normalized to null');
            $this->assertEqualsCanonicalizing(
                $dungeons->pluck('id')->toArray(),
                $season->seasonDungeons()->pluck('dungeon_id')->toArray(),
            );
        } finally {
            $season->syncDungeons([]);
            $season->delete();
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
    public function savenew_givenNoId_returnsValidationError(): void
    {
        // Arrange
        $expansion = Expansion::query()->firstOrFail();

        // Act
        $response = $this->post(route('admin.season.savenew'), [
            'expansion_id'            => $expansion->id,
            'seasonal_affix_id'       => -1,
            'index'                   => 996,
            'start'                   => '2026-01-01 00:00:00',
            'affix_group_count'       => 4,
            'start_affix_group_index' => 0,
            'key_level_min'           => 2,
            'key_level_max'           => 30,
        ]);

        // Assert
        $response->assertSessionHasErrors('id');
        $this->assertFalse(Season::query()->where('index', 996)->exists());
    }

    #[Test]
    public function savenew_givenIdNotDeclaredAsSeasonConstant_returnsValidationError(): void
    {
        // Arrange
        $expansion = Expansion::query()->firstOrFail();

        // Act
        $response = $this->post(route('admin.season.savenew'), [
            'id'                      => 90000,
            'expansion_id'            => $expansion->id,
            'seasonal_affix_id'       => -1,
            'index'                   => 995,
            'start'                   => '2026-01-01 00:00:00',
            'affix_group_count'       => 4,
            'start_affix_group_index' => 0,
            'key_level_min'           => 2,
            'key_level_max'           => 30,
        ]);

        // Assert
        $response->assertSessionHasErrors('id');
        $this->assertFalse(Season::query()->where('index', 995)->exists());
    }

    #[Test]
    public function savenew_givenIdAlreadyTaken_returnsValidationError(): void
    {
        // Arrange
        $expansion     = Expansion::query()->firstOrFail();
        $alreadyUsedId = Season::query()->value('id');

        // Act
        $response = $this->post(route('admin.season.savenew'), [
            'id'                      => $alreadyUsedId,
            'expansion_id'            => $expansion->id,
            'seasonal_affix_id'       => -1,
            'index'                   => 992,
            'start'                   => '2026-01-01 00:00:00',
            'affix_group_count'       => 4,
            'start_affix_group_index' => 0,
            'key_level_min'           => 2,
            'key_level_max'           => 30,
        ]);

        // Assert
        $response->assertSessionHasErrors('id');
        $this->assertFalse(Season::query()->where('index', 992)->exists());
    }

    #[Test]
    public function savenew_givenAvailableDeclaredId_persistsThatExactIdRatherThanAnAutoIncrementedOne(): void
    {
        // Arrange - the fixture DB has every Season::SEASON_* constant already seeded (see
        // create_givenAllDeclaredSeasonIdsAlreadyUsed_showsNoAvailableIdsWarning), so there is
        // never a "naturally" free id to post. Free one up for the duration of this test only,
        // inside a DB transaction that is always rolled back, instead of permanently mutating the
        // shared season fixtures. This is the regression test for SeasonController::store()'s
        // explicit `$season->id = $id` assignment: it must persist the requested constant, not
        // whatever id MySQL's AUTO_INCREMENT would hand out.
        //
        // Season is a CacheModel (laravel-model-caching): the create below fires Eloquent events
        // and flushes/repopulates the cache with this test's throwaway data, but DB::rollBack()
        // only undoes the MySQL rows, not the shared Redis cache - flush it by hand afterwards so
        // a stale entry can't leak into the shared dev environment or another test.
        $expansion = Expansion::query()->firstOrFail();
        $freedId   = Season::query()->max('id');

        DB::beginTransaction();

        try {
            // Bulk delete via the query builder deliberately - Season::$deleting is guarded to
            // admin-only via SeederModel, and a query-builder delete doesn't fire model events
            // (so it also can't accidentally trigger a cache flush before the transaction is set
            // up), matching the project's established test-cleanup convention for SeederModel
            // models.
            Season::query()->where('id', $freedId)->delete();

            // Act
            $response = $this->post(route('admin.season.savenew'), [
                'id'                      => $freedId,
                'expansion_id'            => $expansion->id,
                'seasonal_affix_id'       => -1,
                'index'                   => 989,
                'start'                   => '2026-01-01 00:00:00',
                'affix_group_count'       => 4,
                'start_affix_group_index' => 0,
                'key_level_min'           => 2,
                'key_level_max'           => 30,
            ]);

            // Assert
            $response->assertRedirect();
            $season = Season::query()->where('index', 989)->firstOrFail();
            $this->assertSame($freedId, $season->id);
        } finally {
            DB::rollBack();
            (new Season())->flushCache();
        }
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
