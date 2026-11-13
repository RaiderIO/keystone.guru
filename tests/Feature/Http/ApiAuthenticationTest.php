<?php

namespace Tests\Feature\Http;

use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\User\ApiTokenScope;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Traits\UsesPassportKeys;
use Tests\TestCases\PublicTestCase;

/**
 * API v1 accepts OAuth bearer tokens, scoped per route group, and keeps accepting the deprecated Basic
 * authentication - with Deprecation/Sunset headers - until its sunset.
 */
#[Group('Api')]
#[Group('ApiAuthentication')]
final class ApiAuthenticationTest extends PublicTestCase
{
    use UsesPassportKeys;

    private const string PASSWORD = 'a-password-for-this-test';

    /** @var array<int, User> */
    private array $createdUsers = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->usePassportKeys();
    }

    #[\Override]
    protected function tearDown(): void
    {
        foreach ($this->createdUsers as $user) {
            Passport::token()->newQuery()->where('user_id', $user->id)->delete();
            $user->syncRoles([]);
            $user->delete();
        }
        $this->createdUsers = [];

        parent::tearDown();
    }

    #[Test]
    public function handle_givenValidBearerTokenOfAnAdmin_passesTheAdminRoleGate(): void
    {
        // Arrange - the role gate behind the token only passes if the token resolved to its owner
        $admin = $this->createUser(Role::ROLE_ADMIN);
        $token = $admin->createToken('test', [ApiTokenScope::Admin->value])->accessToken;

        // Act
        $response = $this->getJson(route('api.v1.patreon.sync_runs'), $this->bearer($token));

        // Assert
        $response->assertStatus(StatusCode::OK);
        $this->assertFalse($response->headers->has('Deprecation'));
    }

    #[Test]
    public function handle_givenValidBearerTokenOfANonAdmin_failsTheAdminRoleGate(): void
    {
        // Arrange
        $user  = $this->createUser(Role::ROLE_USER);
        $token = $user->createToken('test', [ApiTokenScope::Admin->value])->accessToken;

        // Act
        $response = $this->getJson(route('api.v1.patreon.sync_runs'), $this->bearer($token));

        // Assert - the scope alone never grants admin access
        $response->assertStatus(StatusCode::FORBIDDEN);
    }

    #[Test]
    public function handle_givenGarbageBearerToken_returnsUnauthorized(): void
    {
        // Arrange
        $token = 'not-a-real-token';

        // Act
        $response = $this->getJson(route('api.v1.combatlog.dungeon.index'), $this->bearer($token));

        // Assert
        $response->assertStatus(StatusCode::UNAUTHORIZED);
    }

    #[Test]
    public function handle_givenRevokedBearerToken_returnsUnauthorized(): void
    {
        // Arrange
        $user   = $this->createUser(Role::ROLE_USER);
        $result = $user->createToken('test', [ApiTokenScope::RoutesRead->value]);
        $result->getToken()?->revoke();

        // Act
        $response = $this->getJson(route('api.v1.combatlog.dungeon.index'), $this->bearer($result->accessToken));

        // Assert
        $response->assertStatus(StatusCode::UNAUTHORIZED);
    }

    #[Test]
    public function handle_givenValidBearerToken_authenticates(): void
    {
        // Arrange - positive control for the revoked/garbage cases on the same endpoint
        $user  = $this->createUser(Role::ROLE_USER);
        $token = $user->createToken('test', [ApiTokenScope::RoutesRead->value])->accessToken;

        // Act
        $response = $this->getJson(route('api.v1.combatlog.dungeon.index'), $this->bearer($token));

        // Assert
        $response->assertStatus(StatusCode::OK);
    }

    #[Test]
    public function apiScope_givenAdminTokenWithoutTheAdminScope_returnsForbidden(): void
    {
        // Arrange - an admin passes the role gate, so only the missing scope can refuse this
        $admin = $this->createUser(Role::ROLE_ADMIN);
        $token = $admin->createToken('test', [ApiTokenScope::RoutesRead->value])->accessToken;

        // Act
        $response = $this->getJson(route('api.v1.patreon.sync_runs'), $this->bearer($token));

        // Assert
        $response->assertStatus(StatusCode::FORBIDDEN);
        $response->assertJsonFragment(['error' => __('exceptions.handler.missing_scope', ['scopes' => ApiTokenScope::Admin->value])]);
    }

    #[Test]
    public function apiScope_givenWriteOnlyTokenOnARouteReadEndpoint_isNotRefusedForTheScope(): void
    {
        // Arrange - the route read groups accept either routes scope, so a write-only token can reach the
        // thumbnail endpoint nested under them
        $user  = $this->createUser(Role::ROLE_USER);
        $token = $user->createToken('test', [ApiTokenScope::RoutesWrite->value])->accessToken;

        // Act
        $response = $this->getJson(route('api.v1.route.index'), $this->bearer($token));

        // Assert
        $this->assertNotSame(StatusCode::FORBIDDEN, $response->getStatusCode(), $response->getContent());
        $this->assertNotSame(StatusCode::UNAUTHORIZED, $response->getStatusCode(), $response->getContent());
    }

    #[Test]
    public function apiScope_givenCombatLogOnlyTokenOnARouteReadEndpoint_returnsForbidden(): void
    {
        // Arrange - the negative counterpart of the write-only case on the same endpoint
        $user  = $this->createUser(Role::ROLE_USER);
        $token = $user->createToken('test', [ApiTokenScope::CombatLogRead->value])->accessToken;

        // Act
        $response = $this->getJson(route('api.v1.route.index'), $this->bearer($token));

        // Assert
        $response->assertStatus(StatusCode::FORBIDDEN);
    }

    #[Test]
    public function handle_givenValidBasicCredentials_authenticatesWithDeprecationHeaders(): void
    {
        // Arrange - Basic auth predates scopes, so it keeps full access to a scoped admin endpoint
        $admin = $this->createUser(Role::ROLE_ADMIN);

        // Act
        $response = $this->getJson(route('api.v1.patreon.sync_runs'), [
            'Authorization' => sprintf('Basic %s', base64_encode(sprintf('%s:%s', $admin->email, self::PASSWORD))),
        ]);

        // Assert
        $response->assertStatus(StatusCode::OK);
        $this->assertTrue($response->headers->has('Deprecation'));
        $this->assertSame(
            'Tue, 01 Jun 2027 00:00:00 GMT',
            $response->headers->get('Sunset'),
        );
    }

    #[Test]
    public function handle_givenWrongBasicPassword_returnsUnauthorized(): void
    {
        // Arrange
        $admin = $this->createUser(Role::ROLE_ADMIN);

        // Act
        $response = $this->getJson(route('api.v1.patreon.sync_runs'), [
            'Authorization' => sprintf('Basic %s', base64_encode(sprintf('%s:%s', $admin->email, 'wrong-password'))),
        ]);

        // Assert
        $response->assertStatus(StatusCode::UNAUTHORIZED);
    }

    private function createUser(string $roleName): User
    {
        $user = User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
        $user->addRole(Role::firstWhere('name', $roleName));

        $this->createdUsers[] = $user;

        return $user;
    }

    /**
     * @return array<string, string>
     */
    private function bearer(string $token): array
    {
        return ['Authorization' => sprintf('Bearer %s', $token)];
    }
}
