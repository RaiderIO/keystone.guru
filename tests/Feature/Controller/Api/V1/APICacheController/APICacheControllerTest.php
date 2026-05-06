<?php

namespace Tests\Feature\Controller\Api\V1\APICacheController;

use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICache')]
final class APICacheControllerTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function drop_givenAuthenticatedAdmin_shouldReturnOk(): void
    {
        // Arrange
        /** @var User $admin */
        $admin = User::findOrFail(1);
        $this->assertTrue(
            $admin->hasRole(Role::ROLE_ADMIN),
            'User id=1 must have the admin role for this test (seed the database).',
        );
        $this->be($admin);

        $cacheServiceMock = $this->createMockPublic(CacheServiceInterface::class);
        $cacheServiceMock->expects($this->once())->method('dropCaches');
        app()->instance(CacheServiceInterface::class, $cacheServiceMock);

        Artisan::shouldReceive('call')
            ->with('modelCache:clear')
            ->once()
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->with('keystoneguru:view', ['operation' => 'cache'])
            ->once()
            ->andReturn(0);

        // Act
        $response = $this->postJson(route('api.v1.cache.drop'));

        // Assert
        $response->assertOk();
        $response->assertExactJson(['status' => 'ok']);
    }

    #[Test]
    public function drop_givenAuthenticatedNonAdmin_shouldReturnForbidden(): void
    {
        // Arrange
        /** @var User $nonAdmin */
        $nonAdmin = User::where('email', 'user@app.com')->firstOrFail();
        $this->assertFalse(
            $nonAdmin->hasRole(Role::ROLE_ADMIN),
            'The non-admin fixture user must not have the admin role.',
        );
        $this->be($nonAdmin);

        // Act
        $response = $this->postJson(route('api.v1.cache.drop'));

        // Assert
        $response->assertStatus(StatusCode::FORBIDDEN);
    }

    #[Test]
    public function drop_givenAuthenticatedInternalTeam_shouldReturnForbidden(): void
    {
        // Arrange
        /** @var User $internalTeamUser */
        $internalTeamUser = User::where('email', 'internal_team@app.com')->firstOrFail();
        $this->assertFalse(
            $internalTeamUser->hasRole(Role::ROLE_ADMIN),
            'The internal_team fixture user must not have the admin role.',
        );
        $this->be($internalTeamUser);

        // Act
        $response = $this->postJson(route('api.v1.cache.drop'));

        // Assert
        $response->assertStatus(StatusCode::FORBIDDEN);
    }

    #[Test]
    public function drop_givenUnauthenticated_shouldReturnForbidden(): void
    {
        // Arrange - no authentication

        // Act
        $response = $this->postJson(route('api.v1.cache.drop'));

        // Assert
        $response->assertStatus(StatusCode::FORBIDDEN);
    }
}
