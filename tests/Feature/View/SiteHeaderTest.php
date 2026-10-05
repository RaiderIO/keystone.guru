<?php

namespace Tests\Feature\View;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The header carries a fixed set of category toggles, never destinations - a new feature lands inside a
 * category instead of adding an item to the bar, which is what made the bar overflow the container
 * between 992px and ~1500px (#4465).
 */
#[Group('View')]
#[Group('SiteHeader')]
final class SiteHeaderTest extends PublicTestCase
{
    private const DESKTOP_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';

    #[Test]
    public function home_givenAGuest_showsTheCategoryTogglesAndLogin(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/');

        // Assert
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('id="navCategoryRoutes"', $html);
        $this->assertStringContainsString('id="navCategoryDungeons"', $html);
        $this->assertStringContainsString('id="navCategoryCompendium"', $html);

        $this->assertStringContainsString('data-bs-target="#login_modal"', $html);
        $this->assertStringContainsString('data-bs-target="#register_modal"', $html);
    }

    #[Test]
    public function home_givenAGuestOnRetail_showsTheHeatmapEntry(): void
    {
        // Arrange
        $this->actingAsGuest();
        $retail = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);

        // Act
        $response = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/');

        // Assert
        $response->assertOk();
        $this->assertStringContainsString(
            route('dungeon.heatmap.gameversion', ['gameVersion' => $retail]),
            $response->getContent(),
        );
    }

    #[Test]
    public function home_givenAGuestOnANonRetailGameVersion_omitsTheHeatmapEntry(): void
    {
        // Arrange
        $this->actingAsGuest();
        $mop                     = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_MOP);
        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_MOP;

        try {
            // Act
            $response = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/');

            // Assert - the Dungeons category does render for that game version, just without heatmaps
            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString(route('dungeon.explore.gameversion', ['gameVersion' => $mop]), $html);
            $this->assertStringNotContainsString(route('dungeon.heatmap.gameversion', ['gameVersion' => $mop]), $html);
            $this->assertStringNotContainsString(__('view_common.layout.header.heatmaps_description'), $html);
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    #[Test]
    public function home_givenAGuest_dropsTheExpansionDropdownAndTheDuplicateDropdownId(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/');

        // Assert
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringNotContainsString('Routes by expansion', $html);
        $this->assertSame(0, substr_count($html, 'id="gameVersionDropdown"'));
        $this->assertSame(1, substr_count($html, 'class="game_version_segments"'));
        $this->assertSame(0, substr_count($html, 'id="languageDropdown"'));
    }

    #[Test]
    public function home_givenAGuest_marksEveryAiTranslatedLanguageWithACornerBadge(): void
    {
        // Arrange
        $this->actingAsGuest();
        /** @var array<int, array<string, mixed>> $allLanguages */
        $allLanguages    = config('language.all', []);
        $allowedCodes    = array_keys(language()->allowed());
        $expectedAiCount = count(array_filter(
            $allLanguages,
            static fn(array $language): bool => in_array($language['long'], $allowedCodes, true)
                && ($language['ai'] ?? false) === true,
        ));

        // Act
        $response = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/');

        // Assert
        $response->assertOk();
        $html = $response->getContent();

        $this->assertGreaterThan(0, $expectedAiCount);
        $this->assertSame($expectedAiCount, substr_count($html, 'ksg-nav-prefs-ai'));
        // An inline superscript widens only the tiles that carry it, which staggered the flag grid.
        $this->assertStringNotContainsString('<sup class="text-warning">AI</sup>', $html);
    }

    #[Test]
    public function home_givenAGuest_marksTheCurrentLocaleAsTheActiveLanguageTile(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/');

        // Assert
        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, 'ksg-nav-prefs-language active'));
    }

    #[Test]
    public function home_givenALoggedInNonAdmin_showsTheAccountMenuWithoutAdminLinks(): void
    {
        // Arrange
        $user = null;

        try {
            $user = User::factory()->create();
            $user->addRole(Role::firstWhere('name', Role::ROLE_USER));

            // Act
            $response = $this->actingAs($user)
                ->withHeader('User-Agent', self::DESKTOP_USER_AGENT)
                ->get('/');

            // Assert
            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString(route('profile.routes'), $html);
            $this->assertStringContainsString(__('view_common.layout.nav.user.preferences'), $html);
            $this->assertStringNotContainsString(route('admin.tools'), $html);
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function home_givenAnAdmin_showsTheAdminSectionLast(): void
    {
        // Arrange
        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');

        // Act
        $response = $this->actingAs($admin)
            ->withHeader('User-Agent', self::DESKTOP_USER_AGENT)
            ->get('/');

        // Assert
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString(route('admin.tools'), $html);

        // route('admin.tools') is a prefix of the other admin tool URLs, so the ordering assertion anchors
        // on a route no other one starts with
        $myRoutesPosition       = strpos($html, route('profile.routes'));
        $adminExpansionPosition = strpos($html, route('admin.expansions'));

        $this->assertNotFalse($myRoutesPosition);
        $this->assertNotFalse($adminExpansionPosition);
        $this->assertLessThan($adminExpansionPosition, $myRoutesPosition, 'The admin section must come after the user\'s own links.');
    }

    #[Test]
    public function category_givenTwoColumns_rendersTheTwoColumnPanel(): void
    {
        // Arrange
        $entries = [
            [
                'route'       => route('compendium.index'),
                'fa'          => 'fas fa-book-open',
                'text'        => 'Overview',
                'description' => 'Everything the community logged this season',
                'strict'      => true,
            ],
            [
                'route'       => route('compendium.activity.index'),
                'fa'          => 'fas fa-stream',
                'text'        => 'Activity',
                'description' => 'The latest combat log observations, by day',
                'strict'      => true,
            ],
        ];

        // Act
        $html = view('common.layout.nav.category', [
            'id'            => 'navCategoryTest',
            'fa'            => 'fas fa-book-open',
            'text'          => 'Compendium',
            'entries'       => $entries,
            'columns'       => 2,
            'isActiveRoute' => fn(string $route, bool $strict = false) => null,
        ])->render();

        // Assert
        $this->assertStringContainsString('ksg-nav-panel ksg-nav-panel--2col', $html);
        $this->assertStringContainsString('ksg-nav-entry--primary', $html);
        $this->assertSame(1, substr_count($html, 'ksg-nav-entry--primary'));
        $this->assertSame(2, substr_count($html, 'ksg-nav-entry-desc'));
    }

    #[Test]
    public function category_givenMenuEndAndModalTab_rendersTheRightAlignedPanelAndTabAttribute(): void
    {
        // Arrange
        $entries = [
            [
                'modal'       => '#edit_route_admin_settings_modal',
                'modalTab'    => '#combatlog_info_tab',
                'fa'          => 'fas fa-scroll',
                'text'        => 'Combatlog info',
                'description' => 'The combat log run this route was created from',
            ],
        ];

        // Act
        $html = view('common.layout.nav.category', [
            'id'            => 'navCategoryTest',
            'fa'            => 'fas fa-toolbox',
            'text'          => 'Developer',
            'entries'       => $entries,
            'menuEnd'       => true,
            'isActiveRoute' => fn(string $route, bool $strict = false) => null,
        ])->render();

        // Assert
        $this->assertStringContainsString('dropdown-menu-end', $html);
        $this->assertStringContainsString('data-bs-target="#edit_route_admin_settings_modal"', $html);
        $this->assertStringContainsString('data-modal-tab="#combatlog_info_tab"', $html);
    }

    #[Test]
    public function category_givenNoMenuEndOrModalTab_rendersNeither(): void
    {
        // Arrange
        $entries = [
            [
                'modal'       => '#create_route_modal',
                'fa'          => 'fas fa-plus',
                'text'        => 'Create route',
                'description' => 'Plan your own route on the dungeon map',
            ],
        ];

        // Act
        $html = view('common.layout.nav.category', [
            'id'            => 'navCategoryTest',
            'fa'            => 'fa fa-route',
            'text'          => 'Routes',
            'entries'       => $entries,
            'isActiveRoute' => fn(string $route, bool $strict = false) => null,
        ])->render();

        // Assert
        $this->assertStringNotContainsString('dropdown-menu-end', $html);
        $this->assertStringNotContainsString('data-modal-tab', $html);
    }

    #[Test]
    public function header_givenDeveloperEntries_rendersTheDeveloperSectionBeforeCreateRoute(): void
    {
        // Arrange
        $developerEntries = [
            [
                'route'       => 'https://example.test/developer',
                'fa'          => 'fas fa-cog',
                'text'        => 'Edit mapping version',
                'description' => 'Open the mapping editor',
            ],
        ];

        // Act
        $html = view('common.layout.header', ['developerEntries' => $developerEntries])->render();

        // Assert
        $developerPosition   = strpos($html, 'id="navCategoryDeveloper"');
        $createRoutePosition = strpos($html, 'data-bs-target="#create_route_modal"', (int)$developerPosition);

        $this->assertNotFalse($developerPosition);
        $this->assertStringContainsString(__('view_common.layout.header.category_developer'), $html);
        $this->assertStringContainsString('https://example.test/developer', $html);
        // The Routes category also opens the create route modal, so this anchors on the one after the section
        $this->assertNotFalse($createRoutePosition, 'The Developer section must come before the Create route button.');
    }

    #[Test]
    public function header_givenNoDeveloperEntries_omitsTheDeveloperSection(): void
    {
        // Act
        $html = view('common.layout.header')->render();

        // Assert
        $this->assertStringNotContainsString('id="navCategoryDeveloper"', $html);
    }

    #[Test]
    public function home_givenAnAdmin_omitsTheDeveloperSection(): void
    {
        // Arrange
        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');

        // Act
        $response = $this->actingAs($admin)
            ->withHeader('User-Agent', self::DESKTOP_USER_AGENT)
            ->get('/');

        // Assert
        $response->assertOk();
        $this->assertStringNotContainsString('id="navCategoryDeveloper"', $response->getContent());
    }

    #[Test]
    public function home_givenAGuest_linksFindRoutesToTheDungeonRouteSearch(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/');

        // Assert
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString(sprintf('href="%s"', route('dungeon.dungeonroute.search')), $html);
        $this->assertStringNotContainsString(sprintf('href="%s"', route('dungeonroutes.search')), $html);
    }

    #[Test]
    public function home_givenAGuest_rendersTheSkipLinkAheadOfTheHeaderAndItsTargetAfterIt(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $html = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/')->assertOk()->getContent();

        // Assert
        $this->assertSame(1, preg_match(
            sprintf(
                '/<a class="btn btn-accent visually-hidden-focusable skip_link" href="#main_content">%s<\/a>\s*<header\s+id="site_header".*?<\/header>.*?<div id="main_content" class="skip_link_target" tabindex="-1"><\/div>/s',
                preg_quote(e(__('view_common.layout.header.skip_to_content')), '/'),
            ),
            $html,
        ));
        $this->assertSame(1, substr_count($html, 'skip_link"'));
    }

    #[Test]
    public function explore_givenAGuest_pointsTheSkipLinkAtTheMap(): void
    {
        // Arrange
        $this->actingAsGuest();
        $retail = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()->where('active', true)->get()
            ->first(static fn(Dungeon $dungeon) => $dungeon->getCurrentMappingVersionForGameVersion($retail) !== null);

        $this->assertNotNull($dungeon, 'Need a seeded retail dungeon with a retail mapping version');

        // Act
        $html = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->followingRedirects()->get(route('dungeon.explore.gameversion.view', [
            'gameVersion' => $retail,
            'dungeon'     => $dungeon,
        ]))->assertOk()->getContent();

        // Assert
        $this->assertStringContainsString('id="map_header"', $html, 'Expected the map, not the selection page');
        $this->assertStringContainsString('class="btn btn-accent visually-hidden-focusable skip_link" href="#map"', $html);
        $this->assertStringContainsString('<div id="map" ', $html);
        $this->assertStringNotContainsString('href="#main_content"', $html);
    }
}
