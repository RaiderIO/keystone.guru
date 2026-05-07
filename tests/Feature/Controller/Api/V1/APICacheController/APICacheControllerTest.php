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
     * Mock CacheServiceInterface and Artisan so the controller body never actually drops caches
     * or warms views during tests. Applied to every test so that even if the role:admin gate
     * unexpectedly lets a request through, we get a deterministic 200 (which the assertion catches)
     * rather than a 500 from a real cache/view-cache invocation.
     *
     * @throws Exception
     */
    private function stubCacheDependencies(): void
    {
        $cacheServiceMock = $this->createMockPublic(CacheServiceInterface::class);
        app()->instance(CacheServiceInterface::class, $cacheServiceMock);

        Artisan::shouldReceive('call')->andReturn(0);
    }

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
        $this->actingAs($admin);
        $this->stubCacheDependencies();

        // Act
        $response = $this->postJson(route('api.v1.cache.drop'));

        // Assert
        $response->assertOk();
        $response->assertExactJson(['status' => 'ok']);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function drop_givenAuthenticatedNonAdmin_shouldReturnForbidden(): void
    {
        // Arrange
        /** @var User $nonAdmin */
        $nonAdmin = User::factory()->create();

        try {
            $this->assertFalse(
                $nonAdmin->hasRole(Role::ROLE_ADMIN),
                'A freshly factoried user must not have the admin role.',
            );
            $this->actingAs($nonAdmin);
            $this->stubCacheDependencies();

            // Act
            $response = $this->postJson(route('api.v1.cache.drop'));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
            $response->assertJsonStructure(['error']);
        } finally {
            $nonAdmin->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function drop_givenUnauthenticated_shouldReturnForbidden(): void
    {
        // Arrange - no authentication
        $this->stubCacheDependencies();

        // Act
        $response = $this->postJson(route('api.v1.cache.drop'));

        // Assert
        $response->assertStatus(StatusCode::FORBIDDEN);
        $response->assertJsonStructure(['error']);
    }
}
