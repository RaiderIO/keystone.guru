<?php

namespace Tests\Feature\View;

use App\Features\NpcCompendium;
use App\Models\Laratrust\Role;
use App\Models\User;
use Laravel\Pennant\Feature;
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
        // The compendium category is behind an admin-controlled feature flag, so it is only in the bar
        // when the flag is on - asserting it unconditionally would fail on the flag, not on the markup.
        if (Feature::active(NpcCompendium::class)) {
            $this->assertStringContainsString('id="navCategoryCompendium"', $html);
        }

        $this->assertStringContainsString('data-bs-target="#login_modal"', $html);
        $this->assertStringContainsString('data-bs-target="#register_modal"', $html);
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
        $this->assertSame(1, substr_count($html, 'id="gameVersionDropdown"'));
        $this->assertSame(0, substr_count($html, 'id="languageDropdown"'));
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

        $myRoutesPosition   = strpos($html, route('profile.routes'));
        $adminToolsPosition = strpos($html, route('admin.tools'));

        $this->assertNotFalse($myRoutesPosition);
        $this->assertNotFalse($adminToolsPosition);
        $this->assertLessThan($adminToolsPosition, $myRoutesPosition, 'The admin section must come after the user\'s own links.');
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
}
