<?php

namespace Tests\Feature\Http;

use App\Features\ApiTokens;
use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\User\ApiTokenScope;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Traits\UsesPassportKeys;
use Tests\TestCases\PublicTestCase;

/**
 * A signed-in user can only grant an OAuth client access while the ApiTokens feature is active for them.
 */
#[Group('Api')]
#[Group('ApiAuthentication')]
final class OAuthAuthorizationTest extends PublicTestCase
{
    use UsesPassportKeys;

    private const string REDIRECT_URI = 'http://localhost/oauth-test-callback';

    private ?Client $client = null;

    /** @var array<int, User> */
    private array $createdUsers = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->usePassportKeys();
        $this->client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('OAuth authorization test client', [self::REDIRECT_URI]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        foreach ($this->createdUsers as $user) {
            Feature::for($user)->forget(ApiTokens::class);
            $user->syncRoles([]);
            $user->delete();
        }
        $this->createdUsers = [];

        $this->client?->delete();
        $this->client = null;

        parent::tearDown();
    }

    #[Test]
    public function authorize_givenSignedInUserWithFeatureInactive_returnsNotFound(): void
    {
        // Arrange
        $user = $this->createUser(featureActive: false);

        // Act
        $response = $this->actingAs($user)->get($this->authorizeUrl());

        // Assert
        $response->assertStatus(StatusCode::NOT_FOUND);
        $this->assertNull(session('authRequest'));
    }

    #[Test]
    public function authorize_givenSignedInUserWithFeatureActive_showsTheConsentScreen(): void
    {
        // Arrange - positive control on the same client and request
        $user = $this->createUser(featureActive: true);

        // Act
        $response = $this->actingAs($user)->get($this->authorizeUrl());

        // Assert
        $response->assertOk();
        $response->assertSee('OAuth authorization test client');
        $this->assertNotNull(session('authRequest'));
    }

    #[Test]
    public function authorize_givenSignedInUserWithFeatureActive_rendersBreadcrumbAndApproveAndDenyForms(): void
    {
        // Arrange
        $user = $this->createUser(featureActive: true);

        // Act
        $response = $this->actingAs($user)->get($this->authorizeUrl());

        // Assert
        $response->assertOk();
        $response->assertSee(__('view_oauth.authorize.heading', ['client' => 'OAuth authorization test client']));
        $response->assertSeeInOrder([
            'breadcrumb',
            __('breadcrumbs.home.account_settings'),
            __('breadcrumbs.home.authorize_application'),
        ]);
        $content = (string)$response->getContent();
        // Approve (POST) and deny (DELETE) share the same URL, so each form is counted by its hidden fields
        $this->assertSame(2, substr_count($content, sprintf('action="%s"', route('passport.authorizations.approve'))));
        $this->assertSame(1, substr_count($content, '<input type="hidden" name="_method" value="DELETE">'));
        $this->assertSame(2, substr_count($content, '<input type="hidden" name="state" value="oauth-test-state">'));
        $this->assertSame(2, substr_count($content, sprintf('<input type="hidden" name="client_id" value="%s">', $this->client?->getKey())));
        $this->assertSame(2, substr_count($content, '<input type="hidden" name="auth_token" value="'));
        $response->assertSee(__('view_oauth.authorize.approve'));
        $response->assertSee(__('view_oauth.authorize.deny'));
    }

    #[Test]
    public function token_givenSessionlessRequest_isNotRefusedByTheGate(): void
    {
        // Arrange - a client exchanging a code carries no user, so only Passport itself may reject it
        $payload = [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->client?->getKey(),
            'client_secret' => $this->client?->plainSecret,
            'redirect_uri'  => self::REDIRECT_URI,
            'code'          => 'not-a-real-code',
        ];

        // Act
        $response = $this->postJson(route('passport.token'), $payload);

        // Assert
        $response->assertStatus(StatusCode::BAD_REQUEST);
        $response->assertJsonFragment(['error' => 'invalid_grant']);
    }

    private function authorizeUrl(): string
    {
        return route('passport.authorizations.authorize', [
            'client_id'     => $this->client?->getKey(),
            'redirect_uri'  => self::REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => ApiTokenScope::RoutesRead->value,
            'state'         => 'oauth-test-state',
        ]);
    }

    private function createUser(bool $featureActive): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));

        $this->createdUsers[] = $user;

        if ($featureActive) {
            Feature::for($user)->activate(ApiTokens::class);
        } else {
            Feature::for($user)->deactivate(ApiTokens::class);
        }

        return $user;
    }
}
