<?php

namespace Tests\Feature\View;

use App\Http\View\Composers\CreateRouteFormComposer;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Season;
use App\Models\User;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\IsolatesSeededUpcomingSeasons;
use Tests\TestCases\PublicTestCase;

/**
 * The Create Route dungeon-selection popup must not offer an upcoming season's dungeons until an
 * admin has marked that season `active` - it is seeded weeks before it starts so its mapping can
 * be reviewed, and until then it should not be selectable (#3868).
 */
#[Group('ViewComposers')]
#[Group('CreateRouteFormComposer')]
final class CreateRouteFormComposerTest extends PublicTestCase
{
    use IsolatesSeededUpcomingSeasons;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsGuest();

        // ViewService caches the season it hands the composer for an hour in the 'tmp_file' store, which - unlike
        // the array store the tests run on - survives between test runs. Seasons created below would otherwise be
        // invisible to the composer, or worse, stay visible to whatever runs next.
        $this->flushSeasonCaches();

        // These tests assert on which season is upcoming site-wide, so a seeded upcoming season would answer
        // for them - and no assertion of "nothing is advertised" could hold at all.
        $this->hideSeededUpcomingSeasons();
    }

    #[Test]
    public function compose_givenAnActiveUpcomingSeason_offersItsNextSeason(): void
    {
        // Arrange - inside the try so a failure halfway through still cleans up
        $upcomingSeason = null;

        try {
            $upcomingSeason = $this->createUpcomingSeason(Carbon::now()->addWeek(), active: true);

            $view = view('common.forms.createroute');

            // Act
            app(CreateRouteFormComposer::class)->compose($view);

            // Assert
            $this->assertSame($upcomingSeason->id, $view->getData()['nextSeason']?->id);
        } finally {
            if ($upcomingSeason !== null) {
                $this->deleteSeason($upcomingSeason);
            }
        }
    }

    #[Test]
    public function compose_givenAnInactiveUpcomingSeason_omitsItFromNextSeason(): void
    {
        // Arrange - the situation a season dry run puts the site in: seeded, but not ready to reveal
        $upcomingSeason = null;

        try {
            $upcomingSeason = $this->createUpcomingSeason(Carbon::now()->addWeek(), active: false);

            $view = view('common.forms.createroute');

            // Act
            app(CreateRouteFormComposer::class)->compose($view);

            // Assert
            $this->assertNull($view->getData()['nextSeason']);
        } finally {
            if ($upcomingSeason !== null) {
                $this->deleteSeason($upcomingSeason);
            }
        }
    }

    /**
     * The Create Route dungeon selector must preselect whatever dungeon the top-bar context is
     * currently on, instead of always falling back to the first option in the list.
     */
    #[Test]
    public function compose_givenADungeonContextCookie_setsCurrentDungeonContextToThatDungeon(): void
    {
        // Arrange
        $expectedDungeon = Dungeon::active()
            ->whereHas('expansion', static fn($q) => $q->where('shortname', 'classic'))
            ->first();
        $this->assertNotNull($expectedDungeon, 'Need at least one active Classic dungeon in the DB');

        $_COOKIE['dungeon_context'] = $expectedDungeon->key;

        try {
            $view = view('common.forms.createroute');

            // Act
            app(CreateRouteFormComposer::class)->compose($view);

            // Assert
            $this->assertSame($expectedDungeon->id, $view->getData()['currentDungeonContext']->id);
        } finally {
            unset($_COOKIE['dungeon_context']);
        }
    }

    /**
     * The actual HTML the "Create Route" button lazy-loads (via the whitelisted ajax.view
     * endpoint - see AjaxViewFormRequest) must mark the logged-in user's dungeon context as
     * selected, not just leave the composer's view data correct.
     */
    #[Test]
    public function ajaxView_givenALoggedInUserWithADungeonContext_preselectsThatDungeonInTheCreateRouteForm(): void
    {
        // Arrange - must be a dungeon the form's selector actually lists (the current season's
        // dungeons), and not the first one in that list, or the pre-fix default would pass by luck
        $currentSeason = app(ViewServiceInterface::class)
            ->getCurrentSeasonForRegion(app(RequestViewContextInterface::class)->getUserOrDefaultRegion());
        $seasonDungeons = $currentSeason->dungeons()->active()->get();
        $this->assertGreaterThan(1, $seasonDungeons->count(), 'Need at least two active current-season dungeons');
        $expectedDungeon = $seasonDungeons->last();

        $user = User::factory()->create(['dungeon_id' => $expectedDungeon->id]);

        try {
            // Act
            $response = $this->actingAs($user)
                ->get(route('ajax.view', ['view' => 'common.modal.createroute']), ['X-Requested-With' => 'XMLHttpRequest']);

            // Assert
            $response->assertOk();
            $response->assertSee(
                sprintf('value="%d" selected="selected"', $expectedDungeon->id),
                false,
            );
        } finally {
            $user->delete();
        }
    }

    /**
     * In the local environment ViewService hands every caller the same Season instance, so a relation
     * the create-route form hides would disappear from the affix picker rendered inside it as well.
     */
    #[Test]
    public function compose_givenASharedCurrentSeason_leavesItsRelationsVisibleToOtherViews(): void
    {
        // Arrange
        $currentSeason = app(ViewServiceInterface::class)
            ->getCurrentSeasonForRegion(app(RequestViewContextInterface::class)->getUserOrDefaultRegion())
            ->load(['dungeons.floors']);

        $this->instance(ViewServiceInterface::class, Mockery::mock(ViewServiceInterface::class, static function (MockInterface $mock) use ($currentSeason): void {
            $mock->shouldReceive('getCurrentSeasonForRegion')->andReturn($currentSeason);
            $mock->shouldReceive('getNextSeasonForRegion')->andReturn(null);
        }));

        $view = view('common.forms.createroute');

        // Act
        app(CreateRouteFormComposer::class)->compose($view);

        // Assert
        $sharedSeason = $currentSeason->toArray();
        $this->assertArrayHasKey('dungeons', $sharedSeason);
        $this->assertArrayHasKey('expansion', $sharedSeason);

        $composedSeason = $view->getData()['currentSeason']->toArray();
        $this->assertArrayNotHasKey('dungeons', $composedSeason);
        $this->assertArrayHasKey('season_dungeons', $composedSeason);
    }

    private function deleteSeason(Season $season): void
    {
        $season->delete();

        $this->flushSeasonCaches();
    }

    private function flushSeasonCaches(): void
    {
        Cache::store('tmp_file')->flush();
    }

    private function createUpcomingSeason(Carbon $start, bool $active): Season
    {
        $expansion = Expansion::firstWhere('shortname', Expansion::EXPANSION_MIDNIGHT);

        return Season::create([
            'expansion_id'            => $expansion->id,
            'seasonal_affix_id'       => null,
            'index'                   => 2,
            'start'                   => $start->toDateTimeString(),
            'active'                  => $active,
            'presets'                 => 0,
            'affix_group_count'       => 8,
            'start_affix_group_index' => 0,
            'key_level_min'           => 2,
            'key_level_max'           => 25,
            'item_level_min'          => 240,
            'item_level_max'          => 300,
        ]);
    }
}
