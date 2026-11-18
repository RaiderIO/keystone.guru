<?php

namespace Tests\Feature\View;

use App\Logging\SensitiveDataRedactor;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('RollbarComposer')]
final class RollbarComposerTest extends PublicTestCase
{
    private const string VIEW = 'common.thirdparty.rollbar.rollbar';

    #[Test]
    public function compose_givenNoClientAccessToken_rendersNoSnippet(): void
    {
        // Arrange
        config(['keystoneguru.rollbar.client_access_token' => null]);

        // Act
        $html = view(self::VIEW)->render();

        // Assert
        $this->assertSame('', trim($html));
    }

    #[Test]
    public function compose_givenClientAccessToken_rendersTokenScrubFieldsEnvironmentAndCodeVersion(): void
    {
        // Arrange
        config(['keystoneguru.rollbar.client_access_token' => 'test-client-token']);

        // Act
        $rollbarConfig = $this->renderRollbarConfig();

        // Assert
        $this->assertSame('test-client-token', $rollbarConfig['accessToken']);
        $this->assertSame(SensitiveDataRedactor::browserScrubFields(), $rollbarConfig['scrubFields']);
        $this->assertSame(config('app.env'), $rollbarConfig['payload']['environment']);
        $this->assertNotEmpty($rollbarConfig['payload']['client']['javascript']['code_version']);
        $this->assertGreaterThan(0, $rollbarConfig['itemsPerMinute']);
        $this->assertGreaterThan(0, $rollbarConfig['maxItems']);
    }

    #[Test]
    public function compose_givenGuest_rendersNoPerson(): void
    {
        // Arrange
        config(['keystoneguru.rollbar.client_access_token' => 'test-client-token']);

        // Act
        $rollbarConfig = $this->renderRollbarConfig();

        // Assert
        $this->assertArrayHasKey('payload', $rollbarConfig);
        $this->assertArrayNotHasKey('person', $rollbarConfig['payload']);
    }

    #[Test]
    public function compose_givenLoggedInUser_rendersPersonIdAndName(): void
    {
        // Arrange
        config(['keystoneguru.rollbar.client_access_token' => 'test-client-token']);
        $user = User::factory()->create();

        try {
            $this->actingAs($user);

            // Act
            $rollbarConfig = $this->renderRollbarConfig();

            // Assert
            $this->assertSame(['id' => (string)$user->id, 'username' => $user->name], $rollbarConfig['payload']['person'] ?? null);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function compose_givenUserNameWithScriptTag_escapesItInTheSnippet(): void
    {
        // Arrange
        config(['keystoneguru.rollbar.client_access_token' => 'test-client-token']);
        $user = User::factory()->create();

        try {
            User::query()->whereKey($user->id)->update(['name' => '</script><script>alert(1)</script>']);
            $this->actingAs($user->refresh());

            // Act
            $html = view(self::VIEW)->render();

            // Assert
            $this->assertStringNotContainsString('</script><script>alert(1)', $html);
            $this->assertSame('</script><script>alert(1)</script>', $this->parseRollbarConfig($html)['payload']['person']['username'] ?? null);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function compose_givenAbsoluteAssetsBaseUrl_passesItsOriginToTheIgnoreCheck(): void
    {
        // Arrange
        config([
            'keystoneguru.rollbar.client_access_token' => 'test-client-token',
            'keystoneguru.assets_base_url'             => 'https://assets.example.com:8443/some/path',
        ]);

        // Act
        $view = view(self::VIEW);
        $view->render();

        // Assert
        $this->assertSame(['https://assets.example.com:8443'], $view->getData()['rollbarOwnOrigins']);
    }

    #[Test]
    public function compose_givenRelativeAssetsBaseUrl_passesNoExtraOrigin(): void
    {
        // Arrange
        config([
            'keystoneguru.rollbar.client_access_token' => 'test-client-token',
            'keystoneguru.assets_base_url'             => '/',
        ]);

        // Act
        $view = view(self::VIEW);
        $view->render();

        // Assert
        $this->assertSame([], $view->getData()['rollbarOwnOrigins']);
    }

    #[Test]
    public function index_givenClientAccessToken_includesTheSnippetInTheLayout(): void
    {
        // Arrange
        config(['keystoneguru.rollbar.client_access_token' => 'test-client-token']);

        // Act
        $response = $this->get(route('home'));

        // Assert
        $response->assertOk();
        $response->assertSee('var _rollbarConfig =', false);
    }

    #[Test]
    public function index_givenNoClientAccessToken_leavesTheSnippetOutOfTheLayout(): void
    {
        // Arrange
        config(['keystoneguru.rollbar.client_access_token' => null]);

        // Act
        $response = $this->get(route('home'));

        // Assert
        $response->assertOk();
        $response->assertSee('</head>', false);
        $response->assertDontSee('var _rollbarConfig =', false);
    }

    /**
     * @return array<string, mixed>
     */
    private function renderRollbarConfig(): array
    {
        return $this->parseRollbarConfig(view(self::VIEW)->render());
    }

    /**
     * @return array<string, mixed>
     */
    private function parseRollbarConfig(string $html): array
    {
        $this->assertSame(1, preg_match('/var _rollbarConfig = (\{.*?\});\n/', $html, $matches));

        return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
