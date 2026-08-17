<?php

namespace Tests\Feature\View;

use App\Http\View\Composers\HeaderComposer;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\User;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\IsolatesSeededUpcomingSeasons;
use Tests\TestCases\PublicTestCase;

/**
 * The dungeon context bar follows the current season only. The upcoming season is advertised next to it
 * as a card of its own, but only once an admin has marked it `active` - seasons are seeded weeks before
 * they start so their mapping can be reviewed, and until then they should not show up anywhere
 * (#3761, #3868).
 */
#[Group('ViewComposers')]
#[Group('HeaderComposer')]
final class HeaderComposerTest extends PublicTestCase
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
    public function compose_givenAnActiveUpcomingSeason_setsTheNextSeasonCard(): void
    {
        // Arrange - inside the try so a failure halfway through still cleans up
        $upcomingSeason = null;

        try {
            $upcomingSeason = $this->createUpcomingSeason(Carbon::now()->addWeek(), active: true);

            $view = view('common.layout.header');

            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $data = $view->getData();

            $this->assertNotNull($data['dungeonContextNextSeason']);
            $this->assertSame($upcomingSeason->id, $data['dungeonContextNextSeason']->id);
            $this->assertStringContainsString(sprintf('season=%d', $upcomingSeason->id), $data['dungeonContextNextSeasonLink']);
        } finally {
            if ($upcomingSeason !== null) {
                $this->deleteSeason($upcomingSeason);
            }
        }
    }

    #[Test]
    public function compose_givenAnInactiveUpcomingSeason_omitsTheNextSeasonEverywhere(): void
    {
        // Arrange - the situation a season dry run puts the site in: seeded, but not ready to reveal
        $upcomingSeason = null;

        try {
            $upcomingSeason = $this->createUpcomingSeason(Carbon::now()->addWeek(), active: false);

            $view = view('common.layout.header');

            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $data = $view->getData();

            $this->assertNull($data['dungeonContextNextSeason']);
            $this->assertNull($data['dungeonContextNextSeasonLink']);
        } finally {
            if ($upcomingSeason !== null) {
                $this->deleteSeason($upcomingSeason);
            }
        }
    }

    /**
     * Seasons are a retail concept. The dungeon selection hides every season tab for a game version without
     * them, so advertising an upcoming season there would be a card leading to a page that cannot show it.
     */
    #[Test]
    public function compose_givenAGameVersionWithoutSeasons_omitsTheNextSeasonCard(): void
    {
        // Arrange
        $upcomingSeason = null;
        $user           = null;

        try {
            $upcomingSeason = $this->createUpcomingSeason(Carbon::now()->addWeek(), active: true);

            $classicEra = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);

            $this->assertFalse((bool)$classicEra->has_seasons, 'Classic Era must be a game version without seasons');

            $user = User::factory()->create(['game_version_id' => $classicEra->id]);
            $this->actingAs($user);

            $view = view('common.layout.header');

            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $data = $view->getData();

            $this->assertNull($data['dungeonContextNextSeason']);
            $this->assertNull($data['dungeonContextNextSeasonLink']);

            // The season lookup itself must be unaffected by has_seasons - only the card is gated on it.
            // Without this, the has_seasons gate could be broken (e.g. silently suppressing the season
            // lookup itself) and the assertions above would pass vacuously.
            $region      = app(RequestViewContextInterface::class)->getUserOrDefaultRegion();
            $foundSeason = app(ViewServiceInterface::class)->getNextSeasonForRegion($region);
            $this->assertNotNull($foundSeason, 'The season should still be found, just not advertised as a card');
            $this->assertSame($upcomingSeason->id, $foundSeason->id);
        } finally {
            $user?->delete();

            if ($upcomingSeason !== null) {
                $this->deleteSeason($upcomingSeason);
            }
        }
    }

    /**
     * Map pages pass `showExpansionNav => false` to reclaim header space; every other page renders
     * with the default (#4024).
     */
    #[Test]
    public function render_givenShowExpansionNavDefault_rendersTheDropdown(): void
    {
        // Act
        $html = view('common.layout.header')->render();

        // Assert
        $this->assertStringContainsString(__('view_common.layout.header.browse_by_expansion'), $html);
    }

    #[Test]
    public function render_givenShowExpansionNavFalse_omitsTheDropdown(): void
    {
        // Act
        $html = view('common.layout.header', ['showExpansionNav' => false])->render();

        // Assert
        $this->assertStringNotContainsString(__('view_common.layout.header.browse_by_expansion'), $html);
    }

    /**
     * The desktop dungeon-context strip is hidden below `lg`, so the mobile dropdown beside the navbar
     * toggler is the only way to switch dungeon on a phone (#4097).
     */
    #[Test]
    public function render_givenShowDungeonContextDefault_rendersTheMobileDungeonSelector(): void
    {
        // Arrange
        $dungeon = Dungeon::getUserOrDefaultDungeon();

        // Act
        $selector = $this->getMobileDungeonSelectorHtml(view('common.layout.header')->render());

        // Assert
        $this->assertNotNull($selector, 'The mobile dungeon selector should render');
        $this->assertStringContainsString(__('view_common.layout.nav.dungeoncontext.change_dungeon'), $selector);
        $this->assertStringContainsString(route('dungeon.changecontext', ['dungeon' => $dungeon]), $selector);
    }

    /**
     * A dungeon route's map view has no dungeon context to switch - it passes `showDungeonContext => false`
     * for the desktop strip, and the mobile selector must follow it.
     */
    #[Test]
    public function render_givenShowDungeonContextFalse_omitsTheMobileDungeonSelector(): void
    {
        // Act
        $html = view('common.layout.header', ['showDungeonContext' => false])->render();

        // Assert
        $this->assertNull($this->getMobileDungeonSelectorHtml($html));
    }

    /**
     * Explore, heatmap, the compendiums, search and discover all override the dungeon context links so that
     * picking a dungeon keeps you on the page type you were already on. The mobile selector honouring the
     * default instead would silently kick those pages' visitors onto a map.
     */
    #[Test]
    public function render_givenOverriddenDungeonContextLinks_usesThemForTheMobileDungeonSelector(): void
    {
        // Arrange
        $dungeon = Dungeon::getUserOrDefaultDungeon();
        $links   = collect([$dungeon->key => 'https://example.test/overridden']);

        // Act
        $selector = $this->getMobileDungeonSelectorHtml(
            view('common.layout.header', ['dungeonContextLinks' => $links])->render(),
        );

        // Assert
        $this->assertNotNull($selector);
        $this->assertStringContainsString('https://example.test/overridden', $selector);
        $this->assertStringNotContainsString(route('dungeon.changecontext', ['dungeon' => $dungeon]), $selector);
    }

    /**
     * Map pages cap the strip at `maxColCount` dungeons and add a 'more' link into their own full dungeon
     * selection page. The mobile dropdown is capped the same way, so it has to offer the same way out - or
     * the dungeons past the cap become unreachable on a phone.
     */
    #[Test]
    public function render_givenShowMore_rendersTheMoreEntryInTheMobileDungeonSelector(): void
    {
        // Arrange - `maxColCount` is not a header parameter: exactly like the desktop strip's own include,
        // the partial reads it out of the enclosing view scope, which is what lets the cap be forced here
        // instead of depending on how many dungeons the current season happens to seed.
        $links = collect(['more' => 'https://example.test/more']);

        // Act
        $selector = $this->getMobileDungeonSelectorHtml(
            view('common.layout.header', [
                'showMore'            => true,
                'maxColCount'         => 1,
                'dungeonContextLinks' => $links,
            ])->render(),
        );

        // Assert
        $this->assertNotNull($selector);
        $this->assertStringContainsString('https://example.test/more', $selector);
        $this->assertStringContainsString(__('view_common.dungeon.list.more'), $selector);
    }

    /**
     * The rendered `<li>` of the mobile dungeon selector, or null when the header did not render one. Scoped
     * on purpose: the desktop strip carries the same links, so an assertion over the whole header would pass
     * on the desktop markup alone.
     */
    private function getMobileDungeonSelectorHtml(string $html): ?string
    {
        $matched = preg_match('/<li class="nav-item dropdown dungeon_context_nav".*?<\/li>/s', $html, $matches);

        return $matched === 1 ? $matches[0] : null;
    }

    private function deleteSeason(Season $season): void
    {
        $season->delete();

        $this->flushSeasonCaches();
    }

    /**
     * The model events take care of laravel-model-caching; ViewService's 'tmp_file' store is a plain file cache
     * that nothing invalidates, and it holds the season for an hour.
     */
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
