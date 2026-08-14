<?php

namespace Tests\Feature\View;

use App\Models\GameServerRegion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Composition of the shared login/register partials: the OAuth column may only offer regions
 * Battle.net can actually authenticate against, both partials must cross-link to each other, and
 * the Bootstrap 3 classes that have styled nothing since the BS5 migration must be gone (#4004).
 */
#[Group('Auth')]
final class AuthFormCompositionTest extends PublicTestCase
{
    #[Test]
    public function redirectToProvider_givenWorldRegion_returnsNotFound(): void
    {
        // Arrange - `world` is a real game_server_regions row, but no Battle.net endpoint exists
        // for it, so the OAuth URL it used to build could never authenticate anyone

        // Act
        $response = $this->get(route('login.battlenet', ['region' => GameServerRegion::WORLD]));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function redirectToProvider_givenUnknownRegion_returnsNotFound(): void
    {
        // Arrange & Act
        $response = $this->get(route('login.battlenet', ['region' => 'not-a-region']));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    #[DataProvider('battleNetRegionProvider')]
    public function redirectToProvider_givenBattleNetRegion_redirectsToBattleNet(string $region): void
    {
        // Arrange & Act
        $response = $this->get(route('login.battlenet', ['region' => $region]));

        // Assert - a redirect off-site to Battle.net's OAuth authorize endpoint. The host differs
        // per region (China runs on battlenet.com.cn), so assert on the endpoint rather than it
        $response->assertRedirectContains('/oauth/authorize');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function battleNetRegionProvider(): array
    {
        return collect(GameServerRegion::BATTLE_NET_REGIONS)
            ->mapWithKeys(static fn(string $region): array => [$region => [$region]])
            ->toArray();
    }

    #[Test]
    #[DataProvider('authPageProvider')]
    public function authForms_givenAuthPage_doNotOfferWorldAsABattleNetRegion(string $routeName): void
    {
        // Arrange & Act
        $response = $this->get(route($routeName));

        // Assert - the region select holds only the five real Battle.net regions
        $response->assertOk();
        $response->assertSee('value="' . GameServerRegion::AMERICAS . '"', false);
        $response->assertDontSee('value="' . GameServerRegion::WORLD . '"', false);
    }

    #[Test]
    #[DataProvider('authPageProvider')]
    public function authForms_givenAuthPage_carryNoDeadBootstrap3Classes(string $routeName): void
    {
        // Arrange & Act
        $response = $this->get(route($routeName));

        // Assert - every one of these styled nothing under Bootstrap 5
        $response->assertOk();
        $response->assertDontSee('form-horizontal');
        $response->assertDontSee('control-label');
        $response->assertDontSee('col-md-offset');
        $response->assertDontSee('border-white');
        $response->assertDontSee('btn-bnet');
        $response->assertDontSee('google_login_image');
    }

    #[Test]
    #[DataProvider('authPageProvider')]
    public function authForms_givenAuthPage_declareTheStackingBreakpointExplicitly(string $routeName): void
    {
        // Arrange & Act - the columns used to wrap only when min-content forced flex-wrap to give
        // out, which made the same partial render at three different widths

        $response = $this->get(route($routeName));

        // Assert
        $response->assertOk();
        $response->assertSee('col-12 col-lg-6', false);
        $response->assertSee('auth-form-divider', false);
    }

    /**
     * @param array<string, string> $parameters
     */
    #[Test]
    #[DataProvider('passwordPageProvider')]
    public function passwordForms_givenPasswordPage_carryNoDeadBootstrap3Classes(
        string $routeName,
        array  $parameters,
    ): void {
        // Arrange & Act - these two blades carried the only col-md-offset-4 in the codebase, a
        // class Bootstrap 5 does not have at all, on columns with no parent .row
        $response = $this->get(route($routeName, $parameters));

        // Assert
        $response->assertOk();
        $response->assertDontSee('form-horizontal');
        $response->assertDontSee('control-label');
        $response->assertDontSee('col-md-offset');
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function passwordPageProvider(): array
    {
        return [
            'password.request' => ['password.request', []],
            'password.reset'   => ['password.reset', ['token' => 'a-reset-token']],
        ];
    }

    #[Test]
    public function loginForm_givenLoginPage_crossLinksToRegister(): void
    {
        // Arrange & Act
        $response = $this->get(route('login'));

        // Assert
        $response->assertOk();
        $response->assertSee('login_register_link', false);
        $response->assertSee(route('register'), false);
    }

    #[Test]
    public function registerForm_givenRegisterPage_crossLinksToLogin(): void
    {
        // Arrange & Act
        $response = $this->get(route('register'));

        // Assert
        $response->assertOk();
        $response->assertSee('register_login_link', false);
        $response->assertSee(route('login'), false);
    }

    #[Test]
    public function authModals_givenGuestOnHomePage_areDismissableWithEscape(): void
    {
        // Arrange & Act - a guest who opened the wrong modal must be able to get out of it
        $response = $this->get(route('home'));

        // Assert
        $response->assertOk();
        $response->assertSee('data-bs-keyboard="true"', false);
    }

    #[Test]
    public function oauthForm_givenBothModalsInOneDocument_rendersUniqueRegionSelectIds(): void
    {
        // Arrange - the partial renders three times on /login (page form plus both modals), so its
        // ids cannot be derived from the modal flag alone

        // Act
        $content = $this->get(route('login'))->assertOk()->getContent();

        // Assert
        foreach (['login_oauth_battlenet_region', 'modal-login_oauth_battlenet_region', 'modal-register_oauth_battlenet_region'] as $id) {
            $this->assertSame(
                1,
                substr_count((string)$content, sprintf('id="%s"', $id)),
                sprintf('Expected exactly one element with id %s', $id),
            );
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function authPageProvider(): array
    {
        return [
            'login'    => ['login'],
            'register' => ['register'],
        ];
    }
}
