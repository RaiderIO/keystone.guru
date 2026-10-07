<?php

namespace Tests\Feature\View;

use App\Http\View\Composers\HeaderComposer;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\User;
use App\Service\Dungeon\DungeonServiceInterface;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[Test]
    public function compose_givenGuestWithGameVersionCookie_setsTheCookieGameVersionDungeons(): void
    {
        // Arrange
        $classicEra = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);

        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_CLASSIC_ERA;

        try {
            $view = view('common.layout.header');

            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $dungeons = $view->getData()['gameVersionDungeons'];

            $this->assertNotEmpty($dungeons);
            $this->assertEqualsCanonicalizing(
                Dungeon::query()->active()->forGameVersion($classicEra)->pluck('id')->all(),
                $dungeons->pluck('id')->all(),
            );
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    #[Test]
    public function compose_givenAContextDungeonInTheGameVersionsList_selectsIt(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon                    = $this->getClassicEraDungeons()->get(1);
        $_COOKIE['game_version']    = GameVersion::GAME_VERSION_CLASSIC_ERA;
        $_COOKIE['dungeon_context'] = $dungeon->key;
        $view                       = view('common.layout.header');

        try {
            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $this->assertSame($dungeon->id, $view->getData()['dungeonContextSelectedDungeon']?->id);
        } finally {
            unset($_COOKIE['game_version'], $_COOKIE['dungeon_context']);
        }
    }

    /**
     * Switching game version keeps the saved dungeon, which the new version's list may not offer at all.
     */
    #[Test]
    public function compose_givenAContextDungeonOutsideTheGameVersionsList_selectsNothing(): void
    {
        // Arrange
        $retailDungeon              = $this->getRetailDungeonOutsideClassicEra();
        $_COOKIE['game_version']    = GameVersion::GAME_VERSION_CLASSIC_ERA;
        $_COOKIE['dungeon_context'] = $retailDungeon->key;
        $view                       = view('common.layout.header');

        try {
            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $this->assertNull($view->getData()['dungeonContextSelectedDungeon']);
        } finally {
            unset($_COOKIE['game_version'], $_COOKIE['dungeon_context']);
        }
    }

    /**
     * The mobile toggle must not name a dungeon that the sheet it opens does not list.
     */
    #[Test]
    public function render_givenAContextDungeonOutsideTheGameVersionsList_showsNoSelectionInTheMobileToggleAndSheet(): void
    {
        // Arrange
        $retailDungeon              = $this->getRetailDungeonOutsideClassicEra();
        $_COOKIE['game_version']    = GameVersion::GAME_VERSION_CLASSIC_ERA;
        $_COOKIE['dungeon_context'] = $retailDungeon->key;

        try {
            // Act
            $html = view('common.layout.header')->render();

            // Assert
            $toggle = $this->getMobileDungeonToggleHtml($html);
            $sheet  = $this->getMobileDungeonSheetHtml($html);
            $this->assertNotNull($toggle);
            $this->assertNotNull($sheet);
            $this->assertSame(1, preg_match('/<span class="dungeon_context_nav_label[^"]*">\s*(.*?)\s*<\/span>/s', $toggle, $matches));
            $this->assertSame(e(__('view_common.layout.nav.dungeoncontext.no_selection')), $matches[1]);
            $this->assertStringNotContainsString($retailDungeon->getImageUrl(), $toggle);
            $this->assertSame(0, preg_match_all('/<a class="dungeon_sheet_row[^"]*"[^>]*aria-current="true"/', $sheet));
            $this->assertStringContainsString('class="dungeon_sheet_row"', $sheet);
        } finally {
            unset($_COOKIE['game_version'], $_COOKIE['dungeon_context']);
        }
    }

    #[Test]
    public function render_givenAContextDungeonInTheGameVersionsList_namesItInTheMobileToggleAndSheet(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon                    = $this->getClassicEraDungeons()->get(1);
        $_COOKIE['game_version']    = GameVersion::GAME_VERSION_CLASSIC_ERA;
        $_COOKIE['dungeon_context'] = $dungeon->key;

        try {
            // Act
            $html = view('common.layout.header')->render();

            // Assert
            $toggle = $this->getMobileDungeonToggleHtml($html);
            $sheet  = $this->getMobileDungeonSheetHtml($html);
            $this->assertNotNull($toggle);
            $this->assertNotNull($sheet);
            $this->assertSame(1, preg_match('/<span class="dungeon_context_nav_label[^"]*">\s*(.*?)\s*<\/span>/s', $toggle, $matches));
            $this->assertSame(e(__($dungeon->abbreviation)), $matches[1]);
            $this->assertStringContainsString($dungeon->getImageUrl(), $toggle);
            $this->assertSame(1, preg_match_all('/<a class="dungeon_sheet_row[^"]*"[^>]*aria-current="true"/', $sheet));
        } finally {
            unset($_COOKIE['game_version'], $_COOKIE['dungeon_context']);
        }
    }

    /**
     * An aria-label would replace the visible dungeon abbreviation as the button's name, so a voice-control user
     * saying what they see would not reach it (WCAG 2.5.3). The name is its own text: a hidden verb, then the label.
     */
    #[Test]
    public function render_givenAContextDungeonInTheGameVersionsList_namesTheMobileToggleByItsVisibleLabel(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon                    = $this->getClassicEraDungeons()->get(1);
        $_COOKIE['game_version']    = GameVersion::GAME_VERSION_CLASSIC_ERA;
        $_COOKIE['dungeon_context'] = $dungeon->key;

        try {
            // Act
            $html = view('common.layout.header')->render();

            // Assert
            $document = new DOMDocument();
            libxml_use_internal_errors(true);
            $document->loadHTML(sprintf('<?xml encoding="UTF-8"><body>%s</body>', $html), LIBXML_NOERROR);
            libxml_clear_errors();
            /** @var \DOMElement|null $toggle */
            $toggle = (new DOMXPath($document))->query('//*[@id="dungeonContextToggle"]')->item(0);
            $this->assertNotNull($toggle);
            $this->assertFalse($toggle->hasAttribute('aria-label'));
            $this->assertSame(
                sprintf('%s %s', __('view_common.layout.nav.dungeoncontext.change_dungeon'), __($dungeon->abbreviation)),
                trim((string)preg_replace('/\s+/', ' ', $toggle->textContent)),
            );
        } finally {
            unset($_COOKIE['game_version'], $_COOKIE['dungeon_context']);
        }
    }

    /**
     * The "Routes by expansion" dropdown was cut from the bar in #4465 - every destination now lives
     * inside a category panel. The map view still passes `showExpansionNav`, so the header must keep
     * accepting it and render identically either way.
     */
    #[Test]
    public function render_givenShowExpansionNavEitherWay_rendersTheSameHeader(): void
    {
        // Act
        $default  = view('common.layout.header')->render();
        $disabled = view('common.layout.header', ['showExpansionNav' => false])->render();

        // Assert
        $this->assertStringNotContainsString('Routes by expansion', $default);
        $this->assertSame($default, $disabled);
    }

    /**
     * The desktop dungeon-context strip is hidden below `lg`, so the toggle beside the navbar toggler and the
     * sheet it opens are the only way to switch dungeon on a phone.
     */
    #[Test]
    public function render_givenShowDungeonContextDefault_rendersTheMobileDungeonToggleAndSheet(): void
    {
        // Arrange
        $dungeon = Dungeon::getUserOrDefaultDungeon();

        // Act
        $html = view('common.layout.header')->render();

        // Assert
        $toggle = $this->getMobileDungeonToggleHtml($html);
        $sheet  = $this->getMobileDungeonSheetHtml($html);
        $this->assertNotNull($toggle, 'The mobile dungeon toggle should render');
        $this->assertStringContainsString('data-bs-target="#dungeon_sheet"', $toggle);
        $this->assertNotNull($sheet, 'The mobile dungeon sheet should render');
        $this->assertStringContainsString(__('view_common.layout.nav.dungeoncontext.change_dungeon'), $sheet);
        $this->assertStringContainsString(route('dungeon.changecontext', ['dungeon' => $dungeon]), $sheet);
    }

    /**
     * A dungeon route's map view has no dungeon context to switch - it passes `showDungeonContext => false`
     * for the desktop strip, and the mobile toggle and sheet must follow it.
     */
    #[Test]
    public function render_givenShowDungeonContextFalse_omitsTheMobileDungeonToggleAndSheet(): void
    {
        // Act
        $html = view('common.layout.header', ['showDungeonContext' => false])->render();

        // Assert
        $this->assertNull($this->getMobileDungeonToggleHtml($html));
        $this->assertNull($this->getMobileDungeonSheetHtml($html));
    }

    /**
     * The game version switch sits on top of the dungeon sheet, and only there - the navbar menu does not
     * carry a second copy.
     */
    #[Test]
    public function render_givenShowDungeonContextDefault_putsTheGameVersionSwitchInTheSheetOnly(): void
    {
        // Arrange
        $gameVersions   = app(ViewServiceInterface::class)->getAllGameVersions();
        $currentVersion = GameVersion::getUserOrDefaultGameVersion();

        // Act
        $html = view('common.layout.header')->render();

        // Assert
        $sheet = $this->getMobileDungeonSheetHtml($html);
        $this->assertNotNull($sheet);
        $this->assertGreaterThan(1, $gameVersions->count());
        $this->assertSame($gameVersions->count(), preg_match_all('/class="game_version_segment[" ]/', $sheet));
        $this->assertSame($gameVersions->count(), preg_match_all('/class="game_version_segment[" ]/', $html));
        $this->assertMatchesRegularExpression(
            sprintf('/href="%s"\s+data-current="true"\s+aria-current="true"/', preg_quote(route('gameversion.update', ['gameVersion' => $currentVersion]), '/')),
            $sheet,
        );
        // The sheet restores the selection from data-current when the page returns from the back/forward cache
        $this->assertSame(1, substr_count($sheet, 'data-current="true"'));
        $this->assertSame($gameVersions->count() - 1, substr_count($sheet, 'data-current="false"'));
    }

    /**
     * Without a dungeon sheet there is nothing to put the game version switch on top of, so the navbar menu
     * keeps it rather than leaving the page without one.
     */
    #[Test]
    public function render_givenShowDungeonContextFalse_keepsTheGameVersionSwitchInTheNavbarMenu(): void
    {
        // Arrange
        $gameVersions = app(ViewServiceInterface::class)->getAllGameVersions();

        // Act
        $html = view('common.layout.header', ['showDungeonContext' => false])->render();

        // Assert
        $this->assertSame(1, preg_match('/<div class="collapse navbar-collapse.*<\/nav>/s', $html, $matches));
        $this->assertSame($gameVersions->count(), preg_match_all('/class="game_version_segment[" ]/', $matches[0]));
    }

    /**
     * Explore, heatmap, the compendiums, search and discover all override the dungeon context links so that
     * picking a dungeon keeps you on the page type you were already on. The mobile sheet honouring the
     * default instead would silently kick those pages' visitors onto a map.
     */
    #[Test]
    public function render_givenOverriddenDungeonContextLinks_usesThemForTheMobileDungeonSheet(): void
    {
        // Arrange
        $dungeon = Dungeon::getUserOrDefaultDungeon();
        $links   = collect([$dungeon->key => 'https://example.test/overridden']);

        // Act
        $sheet = $this->getMobileDungeonSheetHtml(
            view('common.layout.header', ['dungeonContextLinks' => $links])->render(),
        );

        // Assert
        $this->assertNotNull($sheet);
        $this->assertStringContainsString('https://example.test/overridden', $sheet);
        $this->assertStringNotContainsString(route('dungeon.changecontext', ['dungeon' => $dungeon]), $sheet);
    }

    /**
     * Dungeon abbreviations are keystone.guru's own short names, so no game-data sync fills them and every
     * non-English locale file carries them as an empty string - which the header rendered as a blank label.
     */
    #[Test]
    #[DataProvider('render_givenNonEnglishLocale_rendersTheDungeonAbbreviationInThatLocaleOrEnglish_dataProvider')]
    public function render_givenNonEnglishLocale_rendersTheDungeonAbbreviationInThatLocaleOrEnglish(
        string $germanAbbreviation,
        bool   $expectsEnglish,
    ): void {
        // Arrange
        $dungeon             = Dungeon::getUserOrDefaultDungeon();
        $englishAbbreviation = __($dungeon->abbreviation, [], 'en_US');
        $this->assertNotSame('', $englishAbbreviation, 'The default dungeon needs an English abbreviation');
        // Loads the whole group first: addLines() on a group that was never loaded would stand in for all of it
        __($dungeon->abbreviation, [], 'de_DE_ai');
        app('translator')->addLines([$dungeon->abbreviation => $germanAbbreviation], 'de_DE_ai');
        app()->setLocale('de_DE_ai');

        // Act
        $toggle = $this->getMobileDungeonToggleHtml(view('common.layout.header')->render());

        // Assert
        $this->assertNotNull($toggle);
        $this->assertSame(1, preg_match('/<span class="dungeon_context_nav_label[^"]*">\s*(.*?)\s*<\/span>/s', $toggle, $matches));
        $this->assertSame(e($expectsEnglish ? $englishAbbreviation : $germanAbbreviation), $matches[1]);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function render_givenNonEnglishLocale_rendersTheDungeonAbbreviationInThatLocaleOrEnglish_dataProvider(): array
    {
        return [
            'translated'   => ['TESTKÜRZEL', false],
            'untranslated' => ['', true],
        ];
    }

    /**
     * @return Collection<int, Dungeon>
     */
    private function getClassicEraDungeons(): Collection
    {
        return app(DungeonServiceInterface::class)->getDungeonsForGameVersion(GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA));
    }

    private function getRetailDungeonOutsideClassicEra(): Dungeon
    {
        $classicEraDungeonIds = $this->getClassicEraDungeons()->pluck('id');
        /** @var Dungeon|null $dungeon */
        $dungeon = app(DungeonServiceInterface::class)->getDungeonsForGameVersion(GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL))
            ->first(static fn(Dungeon $dungeon) => !$classicEraDungeonIds->contains($dungeon->id));
        $this->assertNotNull($dungeon, 'Need a seeded retail dungeon that Classic Era does not list');

        return $dungeon;
    }

    /**
     * The rendered `<li>` of the mobile dungeon toggle, or null when the header did not render one.
     */
    private function getMobileDungeonToggleHtml(string $html): ?string
    {
        $matched = preg_match('/<li class="nav-item dungeon_context_nav">.*?<\/li>/s', $html, $matches);

        return $matched === 1 ? $matches[0] : null;
    }

    /**
     * The rendered mobile dungeon sheet, or null when the header did not render one. Scoped on purpose: the
     * desktop strip carries the same links, so an assertion over the whole header would pass on the desktop
     * markup alone.
     */
    private function getMobileDungeonSheetHtml(string $html): ?string
    {
        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML(sprintf('<?xml encoding="UTF-8"><body>%s</body>', $html), LIBXML_NOERROR);
        libxml_clear_errors();

        $sheet = (new DOMXPath($document))->query('//*[@id="dungeon_sheet"]')->item(0);

        return $sheet === null ? null : $document->saveHTML($sheet);
    }

    private function deleteSeason(Season $season): void
    {
        $season->delete();

        $this->flushSeasonCaches();
    }

    /**
     * ViewService's 'tmp_file' store is a plain file cache that nothing invalidates, and it holds the
     * season for an hour.
     */
    private function flushSeasonCaches(): void
    {
        Cache::store('tmp_file')->flush();
    }

    private function createUpcomingSeason(Carbon $start, bool $active): Season
    {
        $expansion = Expansion::firstWhere('key', Expansion::EXPANSION_MIDNIGHT);

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
