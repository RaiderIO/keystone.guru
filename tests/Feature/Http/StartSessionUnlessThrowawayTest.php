<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCases\PublicTestCase;

#[Group('Middleware')]
#[Group('StartSessionUnlessThrowaway')]
final class StartSessionUnlessThrowawayTest extends PublicTestCase
{
    private const string BROWSER_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36';

    private const string CRAWLER_USER_AGENT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    /** @var array<string, string> */
    private const array BROWSER_NAVIGATION_HEADERS = [
        'User-Agent'     => self::BROWSER_USER_AGENT,
        'Sec-Fetch-Site' => 'none',
        'Sec-Fetch-Mode' => 'navigate',
        'Sec-Fetch-Dest' => 'document',
    ];

    /** @var array<string, string> */
    private const array CROSS_SITE_IFRAME_HEADERS = [
        'User-Agent'     => self::BROWSER_USER_AGENT,
        'Sec-Fetch-Site' => 'cross-site',
        'Sec-Fetch-Mode' => 'navigate',
        'Sec-Fetch-Dest' => 'iframe',
    ];

    #[Test]
    #[DataProvider('handle_givenANonBrowserClientWithoutASessionCookie_doesNotPersistASession_dataProvider')]
    public function handle_givenANonBrowserClientWithoutASessionCookie_doesNotPersistASession(string $userAgent): void
    {
        // Arrange
        $headers = ['User-Agent' => $userAgent];

        // Act
        $response = $this->withHeaders($headers)->get(route('misc.about'));

        // Assert
        $response->assertSuccessful();
        $this->assertSessionNotPersisted($response);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function handle_givenANonBrowserClientWithoutASessionCookie_doesNotPersistASession_dataProvider(): array
    {
        return [
            'search engine crawler'      => [self::CRAWLER_USER_AGENT],
            'load balancer health check' => ['ELB-HealthChecker/2.0'],
            'link preview'               => ['facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'],
            'no user agent'              => [''],
        ];
    }

    #[Test]
    public function handle_givenABrowserWithoutASessionCookie_persistsTheSession(): void
    {
        // Arrange
        $headers = self::BROWSER_NAVIGATION_HEADERS;

        // Act
        $response = $this->withHeaders($headers)->get(route('misc.about'));

        // Assert
        $response->assertSuccessful();
        $this->assertSessionPersisted($response);
    }

    #[Test]
    public function handle_givenABrowserWithoutFetchMetadata_persistsTheSession(): void
    {
        // Arrange - a browser too old to send Sec-Fetch-* headers
        $headers = ['User-Agent' => self::BROWSER_USER_AGENT];

        // Act
        $response = $this->withHeaders($headers)->get(route('misc.about'));

        // Assert
        $response->assertSuccessful();
        $this->assertSessionPersisted($response);
    }

    #[Test]
    public function handle_givenABrowserWhoseUserAgentMatchesACrawlerPattern_persistsTheSession(): void
    {
        // Arrange - Fetch Metadata only comes from a browser, whatever its user agent claims
        $headers = array_merge(self::BROWSER_NAVIGATION_HEADERS, ['User-Agent' => self::CRAWLER_USER_AGENT]);

        // Act
        $response = $this->withHeaders($headers)->get(route('misc.about'));

        // Assert
        $response->assertSuccessful();
        $this->assertSessionPersisted($response);
    }

    #[Test]
    public function handle_givenACrossSiteIframe_doesNotPersistASession(): void
    {
        // Arrange
        $headers = self::CROSS_SITE_IFRAME_HEADERS;

        // Act
        $response = $this->withHeaders($headers)->get(route('misc.about'));

        // Assert
        $response->assertSuccessful();
        $this->assertSessionNotPersisted($response);
    }

    #[Test]
    public function handle_givenACrossSiteIframeWithSameSiteNoneCookies_persistsTheSession(): void
    {
        // Arrange
        config(['session.same_site' => 'none']);
        $headers = self::CROSS_SITE_IFRAME_HEADERS;

        // Act
        $response = $this->withHeaders($headers)->get(route('misc.about'));

        // Assert
        $response->assertSuccessful();
        $this->assertSessionPersisted($response);
    }

    #[Test]
    public function handle_givenACrossSiteTopLevelNavigation_persistsTheSession(): void
    {
        // Arrange - following a link from another site stores a SameSite=lax cookie just fine
        $headers = array_merge(self::BROWSER_NAVIGATION_HEADERS, ['Sec-Fetch-Site' => 'cross-site']);

        // Act
        $response = $this->withHeaders($headers)->get(route('misc.about'));

        // Assert
        $response->assertSuccessful();
        $this->assertSessionPersisted($response);
    }

    #[Test]
    public function handle_givenACrawlerWhoseRequestWritesToTheSession_persistsTheSession(): void
    {
        // Arrange
        $headers = ['User-Agent' => self::CRAWLER_USER_AGENT];

        // Act - the auth redirect remembers the intended url in the session
        $response = $this->withHeaders($headers)->get(route('profile.edit'));

        // Assert
        $response->assertRedirect(route('login'));
        $this->assertSessionPersisted($response);
    }

    #[Test]
    public function handle_givenACrawlerReturningWithAnExistingSessionCookie_keepsTheSession(): void
    {
        // Arrange
        $sessionId = $this->storeSession(['_token' => Str::random(40)]);
        $headers   = ['User-Agent' => self::CRAWLER_USER_AGENT];

        // Act
        $response = $this->withCookie(config('session.cookie'), $sessionId)
            ->withHeaders($headers)
            ->get(route('misc.about'));

        // Assert
        $response->assertSuccessful();
        $this->assertSessionPersisted($response);
        self::assertSame($sessionId, $this->sessionStore()->getId());
    }

    #[Test]
    public function handle_givenALoggedInUserReturningWithTheirSessionCookie_keepsThemLoggedIn(): void
    {
        // Arrange - a user agent and missing Fetch Metadata that would make a guest's session a throwaway
        $user = User::factory()->create();

        try {
            $sessionId = $this->storeSession([
                '_token'                 => Str::random(40),
                Auth::guard()->getName() => $user->id,
            ]);
            $headers = ['User-Agent' => self::CRAWLER_USER_AGENT];

            // Act
            $response = $this->withCookie(config('session.cookie'), $sessionId)
                ->withHeaders($headers)
                ->get(route('misc.about'));

            // Assert
            $response->assertSuccessful();
            $this->assertAuthenticatedAs($user);
            $this->assertSessionPersisted($response);
            self::assertSame($sessionId, $this->sessionStore()->getId());
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function handle_givenAnAuthenticatedUserWithoutASessionCookie_persistsTheSession(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            $headers = ['User-Agent' => self::CRAWLER_USER_AGENT];

            // Act
            $response = $this->actingAs($user)
                ->withHeaders($headers)
                ->get(route('misc.about'));

            // Assert
            $response->assertSuccessful();
            $this->assertSessionPersisted($response);
        } finally {
            $user->delete();
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function storeSession(array $attributes): string
    {
        $sessionId = Str::random(40);

        $this->sessionStore()->getHandler()->write($sessionId, serialize($attributes));

        return $sessionId;
    }

    private function sessionStore(): Store
    {
        /** @var Store $store */
        $store = $this->app['session']->driver();

        return $store;
    }

    /**
     * @param TestResponse<Response> $response
     */
    private function assertSessionPersisted(TestResponse $response): void
    {
        $response->assertCookie(config('session.cookie'), $this->sessionStore()->getId());
        $response->assertCookie('XSRF-TOKEN');
        self::assertNotSame('', $this->sessionStore()->getHandler()->read($this->sessionStore()->getId()));
    }

    /**
     * @param TestResponse<Response> $response
     */
    private function assertSessionNotPersisted(TestResponse $response): void
    {
        $response->assertCookieMissing(config('session.cookie'));
        $response->assertCookieMissing('XSRF-TOKEN');
        self::assertSame('', $this->sessionStore()->getHandler()->read($this->sessionStore()->getId()));
    }
}
