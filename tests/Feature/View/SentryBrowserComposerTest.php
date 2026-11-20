<?php

namespace Tests\Feature\View;

use App\Http\View\Composers\SentryBrowserComposer;
use App\Logging\SensitiveDataRedactor;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('SentryBrowserComposer')]
final class SentryBrowserComposerTest extends PublicTestCase
{
    private const string VIEW = 'common.thirdparty.sentry.browser';

    private const string DSN = 'https://public-key@o0.ingest.us.sentry.io/0';

    #[Test]
    public function compose_givenNoDsn_rendersNoSnippet(): void
    {
        // Arrange
        config(['keystoneguru.sentry.browser_dsn' => null]);

        // Act
        $html = view(self::VIEW)->render();

        // Assert
        $this->assertSame('', trim($html));
    }

    #[Test]
    public function compose_givenDsn_rendersPinnedSdkWithIntegrity(): void
    {
        // Arrange
        config(['keystoneguru.sentry.browser_dsn' => self::DSN]);

        // Act
        $html = view(self::VIEW)->render();

        // Assert
        $this->assertStringContainsString(
            sprintf('<script src="%s" integrity="%s"', SentryBrowserComposer::SDK_URL, SentryBrowserComposer::SDK_INTEGRITY),
            $html,
        );
    }

    #[Test]
    public function compose_givenDsn_rendersDsnEnvironmentReleaseAndSampling(): void
    {
        // Arrange
        config([
            'keystoneguru.sentry.browser_dsn'         => self::DSN,
            'keystoneguru.sentry.browser_sample_rate' => 0.5,
            'sentry.environment'                      => 'staging',
            'sentry.release'                          => 'v1.2.3',
        ]);

        // Act
        $sentryConfig = $this->renderSentryConfig();

        // Assert
        $this->assertSame(self::DSN, $sentryConfig['dsn']);
        $this->assertSame('staging', $sentryConfig['environment']);
        $this->assertSame('v1.2.3', $sentryConfig['release']);
        $this->assertEquals(0.5, $sentryConfig['sampleRate']);
        $this->assertFalse($sentryConfig['sendDefaultPii']);
        $this->assertNotEmpty($sentryConfig['ignoreErrors']);
    }

    #[Test]
    public function compose_givenDsn_passesTheRedactorScrubFields(): void
    {
        // Arrange
        config(['keystoneguru.sentry.browser_dsn' => self::DSN]);

        // Act
        $view = view(self::VIEW);
        $view->render();

        // Assert
        $this->assertSame(SensitiveDataRedactor::browserScrubFields(), $view->getData()['sentryBrowserScrubFields']);
    }

    #[Test]
    public function compose_givenGuest_rendersNoUser(): void
    {
        // Arrange
        config(['keystoneguru.sentry.browser_dsn' => self::DSN]);

        // Act
        $sentryConfig = $this->renderSentryConfig();

        // Assert
        $this->assertArrayHasKey('initialScope', $sentryConfig);
        $this->assertArrayNotHasKey('user', $sentryConfig['initialScope']);
    }

    #[Test]
    public function compose_givenLoggedInUser_rendersUserIdAndName(): void
    {
        // Arrange
        config(['keystoneguru.sentry.browser_dsn' => self::DSN]);
        $user = User::factory()->create();

        try {
            $this->actingAs($user);

            // Act
            $sentryConfig = $this->renderSentryConfig();

            // Assert
            $this->assertSame(['id' => (string)$user->id, 'username' => $user->name], $sentryConfig['initialScope']['user'] ?? null);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function compose_givenUserNameWithScriptTag_escapesItInTheSnippet(): void
    {
        // Arrange
        config(['keystoneguru.sentry.browser_dsn' => self::DSN]);
        $user = User::factory()->create();

        try {
            User::query()->whereKey($user->id)->update(['name' => '</script><script>alert(1)</script>']);
            $this->actingAs($user->refresh());

            // Act
            $html = view(self::VIEW)->render();

            // Assert
            $this->assertStringNotContainsString('</script><script>alert(1)', $html);
            $this->assertSame('</script><script>alert(1)</script>', $this->parseSentryConfig($html)['initialScope']['user']['username'] ?? null);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function compose_givenAbsoluteAssetsBaseUrl_allowsItsOrigin(): void
    {
        // Arrange
        config([
            'keystoneguru.sentry.browser_dsn' => self::DSN,
            'keystoneguru.assets_base_url'    => 'https://assets.example.com:8443/some/path',
        ]);

        // Act
        $view = view(self::VIEW);
        $view->render();

        // Assert
        $this->assertSame(['https://assets.example.com:8443'], $view->getData()['sentryBrowserOwnOrigins']);
    }

    #[Test]
    public function compose_givenRelativeAssetsBaseUrl_allowsNoExtraOrigin(): void
    {
        // Arrange
        config([
            'keystoneguru.sentry.browser_dsn' => self::DSN,
            'keystoneguru.assets_base_url'    => '/',
        ]);

        // Act
        $view = view(self::VIEW);
        $view->render();

        // Assert
        $this->assertSame([], $view->getData()['sentryBrowserOwnOrigins']);
    }

    #[Test]
    public function index_givenDsn_includesTheSnippetInTheLayout(): void
    {
        // Arrange
        config(['keystoneguru.sentry.browser_dsn' => self::DSN]);

        // Act
        $response = $this->get(route('home'));

        // Assert
        $response->assertOk();
        $response->assertSee(SentryBrowserComposer::SDK_URL, false);
        $response->assertSee('Sentry.init(config);', false);
    }

    #[Test]
    public function index_givenNoDsn_leavesTheSnippetOutOfTheLayout(): void
    {
        // Arrange
        config(['keystoneguru.sentry.browser_dsn' => null]);

        // Act
        $response = $this->get(route('home'));

        // Assert
        $response->assertOk();
        $response->assertSee('</head>', false);
        $response->assertDontSee(SentryBrowserComposer::SDK_URL, false);
        $response->assertDontSee('Sentry.init(config);', false);
    }

    /**
     * @return array<string, mixed>
     */
    private function renderSentryConfig(): array
    {
        return $this->parseSentryConfig(view(self::VIEW)->render());
    }

    /**
     * @return array<string, mixed>
     */
    private function parseSentryConfig(string $html): array
    {
        $this->assertSame(1, preg_match('/\}\)\((\{.*?\}), \[/s', $html, $matches));

        return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
