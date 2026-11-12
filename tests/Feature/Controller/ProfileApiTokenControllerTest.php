<?php

namespace Tests\Feature\Controller;

use App\Features\ApiTokens;
use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\User\ApiTokenScope;
use Laravel\Passport\Passport;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Traits\UsesPassportKeys;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('ApiAuthentication')]
final class ProfileApiTokenControllerTest extends PublicTestCase
{
    use UsesPassportKeys;

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
            Feature::for($user)->forget(ApiTokens::class);
            Passport::token()->newQuery()->where('user_id', $user->id)->delete();
            $user->syncRoles([]);
            $user->delete();
        }
        $this->createdUsers = [];

        parent::tearDown();
    }

    #[Test]
    public function store_givenValidScopes_createsTokenAndFlashesItOnce(): void
    {
        // Arrange
        $user = $this->createUser(Role::ROLE_USER, featureActive: true);

        // Act
        $response = $this->actingAs($user)->post(route('profile.apitoken.store'), [
            'name'   => 'My script',
            'scopes' => [ApiTokenScope::RoutesRead->value],
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('api_token_plain_text');
        $token = Passport::token()->newQuery()->where('user_id', $user->id)->sole();
        $this->assertSame('My script', $token->name);
        $this->assertSame([ApiTokenScope::RoutesRead->value], $token->scopes);
    }

    #[Test]
    public function store_givenAdminScopeAsNonAdmin_failsValidation(): void
    {
        // Arrange
        $user = $this->createUser(Role::ROLE_USER, featureActive: true);

        // Act
        $response = $this->actingAs($user)->post(route('profile.apitoken.store'), [
            'name'   => 'My script',
            'scopes' => [ApiTokenScope::Admin->value],
        ]);

        // Assert
        $response->assertSessionHasErrors(['scopes.0']);
        $this->assertSame(0, Passport::token()->newQuery()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function store_givenAdminScopeAsAdmin_createsToken(): void
    {
        // Arrange - positive control for the non-admin case
        $user = $this->createUser(Role::ROLE_ADMIN, featureActive: true);

        // Act
        $response = $this->actingAs($user)->post(route('profile.apitoken.store'), [
            'name'   => 'Admin script',
            'scopes' => [ApiTokenScope::Admin->value],
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Passport::token()->newQuery()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function store_givenFeatureInactive_createsNoToken(): void
    {
        // Arrange
        $user = $this->createUser(Role::ROLE_USER, featureActive: false);

        // Act
        $response = $this->actingAs($user)->post(route('profile.apitoken.store'), [
            'name'   => 'My script',
            'scopes' => [ApiTokenScope::RoutesRead->value],
        ]);

        // Assert
        $response->assertStatus(StatusCode::NOT_FOUND);
        $this->assertSame(0, Passport::token()->newQuery()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function destroy_givenOwnToken_revokesIt(): void
    {
        // Arrange
        $user  = $this->createUser(Role::ROLE_USER, featureActive: true);
        $token = $user->createToken('mine', [ApiTokenScope::RoutesRead->value])->getToken();

        // Act
        $response = $this->actingAs($user)->delete(route('profile.apitoken.destroy', ['tokenId' => $token->id]));

        // Assert
        $response->assertRedirect();
        $this->assertTrue((bool)$token->fresh()->revoked);
    }

    #[Test]
    public function destroy_givenSomeoneElsesToken_returnsNotFoundAndKeepsIt(): void
    {
        // Arrange
        $owner    = $this->createUser(Role::ROLE_USER, featureActive: true);
        $attacker = $this->createUser(Role::ROLE_USER, featureActive: true);
        $token    = $owner->createToken('theirs', [ApiTokenScope::RoutesRead->value])->getToken();

        // Act
        $response = $this->actingAs($attacker)->delete(route('profile.apitoken.destroy', ['tokenId' => $token->id]));

        // Assert
        $response->assertStatus(StatusCode::NOT_FOUND);
        $this->assertFalse((bool)$token->fresh()->revoked);
    }

    #[Test]
    public function edit_givenFeatureActive_listsActiveTokens(): void
    {
        // Arrange
        $user = $this->createUser(Role::ROLE_USER, featureActive: true);
        $user->createToken('listed token', [ApiTokenScope::RoutesRead->value]);
        $user->createToken('revoked token', [ApiTokenScope::RoutesRead->value])->getToken()?->revoke();

        // Act
        $response = $this->actingAs($user)->get(route('profile.edit'));

        // Assert
        $response->assertOk();
        $response->assertSee('listed token');
        $response->assertDontSee('revoked token');
    }

    private function createUser(string $roleName, bool $featureActive): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', $roleName));

        $this->createdUsers[] = $user;

        if ($featureActive) {
            Feature::for($user)->activate(ApiTokens::class);
        } else {
            Feature::for($user)->deactivate(ApiTokens::class);
        }

        return $user;
    }
}
