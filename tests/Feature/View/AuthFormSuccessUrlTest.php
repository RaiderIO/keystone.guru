<?php

namespace Tests\Feature\View;

use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\View\RequestViewContext;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The auth modals must navigate to the home page instead of reloading whenever the page they were
 * opened on is guest-only - reloading such a page bounces the now-authenticated user off the
 * `guest` middleware, and that extra hop ages out the flashed status message (issue #4003).
 */
#[Group('Auth')]
final class AuthFormSuccessUrlTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('routeNameProvider')]
    public function isCurrentRouteGuestOnly_givenRouteName_returnsWhetherRouteIsBehindGuestMiddleware(
        string $routeName,
        bool   $expected,
    ): void {
        // Arrange - drive the derivation per named route; password.email and password.update are
        // POST-only and so cannot be covered by rendering a page
        $route = Route::getRoutes()->getByName($routeName);
        $this->assertNotNull($route, sprintf('Route %s must exist', $routeName));

        request()->setRouteResolver(fn() => $route);

        // Act
        $isGuestOnly = $this->makeRequestViewContext()->isCurrentRouteGuestOnly();

        // Assert
        $this->assertSame($expected, $isGuestOnly);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function routeNameProvider(): array
    {
        return [
            'register'          => ['register', true],
            'login'             => ['login', true],
            'password.request'  => ['password.request', true],
            'password.email'    => ['password.email', true],
            'password.reset'    => ['password.reset', true],
            'password.update'   => ['password.update', true],
            'home'              => ['home', false],
            'dungeonroute.edit' => ['dungeonroute.edit', false],
        ];
    }

    #[Test]
    public function isCurrentRouteGuestOnly_givenNoRoute_returnsFalse(): void
    {
        // Arrange - there is no route at all while rendering an error page outside of routing
        request()->setRouteResolver(fn() => null);

        // Act
        $isGuestOnly = $this->makeRequestViewContext()->isCurrentRouteGuestOnly();

        // Assert
        $this->assertFalse($isGuestOnly);
    }

    /**
     * @param array<string, string> $parameters
     */
    #[Test]
    #[DataProvider('guestOnlyPageProvider')]
    public function authModal_givenGuestOnlyPage_rendersHomePageAsSuccessUrl(string $routeName, array $parameters): void
    {
        // Arrange
        $expected = self::jsonOption('successUrl', route('home'));

        // Act
        $response = $this->get(route($routeName, $parameters));

        // Assert
        $response->assertOk();
        $response->assertSee($expected, false);
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function guestOnlyPageProvider(): array
    {
        // Only the GET-able guest-only routes can be rendered; password.email and password.update
        // are POST-only and are covered by the derivation test above
        return [
            'register'         => ['register', []],
            'login'            => ['login', []],
            'password.request' => ['password.request', []],
            'password.reset'   => ['password.reset', ['token' => 'a-reset-token']],
        ];
    }

    #[Test]
    public function authModal_givenPublicPage_rendersNoSuccessUrl(): void
    {
        // Arrange
        $expected = self::jsonOption('successUrl', null);

        // Act
        $response = $this->get(route('home'));

        // Assert - the modal is there (the visitor is a guest), it just reloads the page on success
        $response->assertOk();
        $response->assertSee($expected, false);
        $response->assertDontSee(self::jsonOption('successUrl', route('home')), false);
    }

    private function makeRequestViewContext(): RequestViewContext
    {
        // Not resolved from the container: it is bound as scoped, so a second resolve within the
        // same test would hand back the instance that already memoized its answer
        return new RequestViewContext(
            app(ExpansionServiceInterface::class),
            app(GameVersionServiceInterface::class),
        );
    }

    /**
     * Renders a single inline-options key/value exactly as the inline blade json_encodes it.
     */
    private static function jsonOption(string $key, ?string $value): string
    {
        return trim(json_encode([$key => $value], JSON_THROW_ON_ERROR), '{}');
    }
}
