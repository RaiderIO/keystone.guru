<?php

namespace Tests\Feature\Controller\Api\V1\APICacheController;

use App\Jobs\DropCaches;
use App\Models\Laratrust\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICache')]
final class APICacheControllerTest extends PublicTestCase
{
    #[Test]
    public function drop_givenAuthenticatedAdmin_dispatchesDropCachesJobAndReturnsOk(): void
    {
        // Arrange
        Queue::fake();
        /** @var User $admin */
        $admin = User::findOrFail(1);
        $this->assertTrue(
            $admin->hasRole(Role::ROLE_ADMIN),
            'User id=1 must have the admin role for this test (seed the database).',
        );
        $this->actingAs($admin);

        // Act
        $response = $this->postJson(route('api.v1.cache.drop'));

        // Assert
        $response->assertOk();
        $response->assertExactJson(['status' => 'ok']);
        Queue::assertPushed(DropCaches::class);
    }

    #[Test]
    public function drop_givenAiAgent_shouldReturnForbidden(): void
    {
        // Arrange — the agent role is read-only; cache dropping stays admin-only
        Queue::fake();
        /** @var User $aiAgent */
        $aiAgent = User::factory()->create();

        try {
            $aiAgent->addRole(Role::ROLE_AI_AGENT);
            $this->actingAs($aiAgent);

            // Act
            $response = $this->postJson(route('api.v1.cache.drop'));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
            Queue::assertNotPushed(DropCaches::class);
        } finally {
            $aiAgent->delete();
        }
    }

    #[Test]
    public function drop_givenAuthenticatedNonAdmin_shouldReturnForbidden(): void
    {
        // Arrange
        Queue::fake();
        /** @var User $nonAdmin */
        $nonAdmin = User::factory()->create();

        try {
            $this->assertFalse(
                $nonAdmin->hasRole(Role::ROLE_ADMIN),
                'A freshly factoried user must not have the admin role.',
            );
            $this->actingAs($nonAdmin);

            // Act
            $response = $this->postJson(route('api.v1.cache.drop'));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
            $response->assertJsonStructure(['error']);
            Queue::assertNotPushed(DropCaches::class);
        } finally {
            $nonAdmin->delete();
        }
    }

    #[Test]
    public function drop_givenUnauthenticated_shouldReturnForbidden(): void
    {
        // Arrange - no authentication
        Queue::fake();

        // Act
        $response = $this->postJson(route('api.v1.cache.drop'));

        // Assert
        $response->assertStatus(StatusCode::FORBIDDEN);
        $response->assertJsonStructure(['error']);
        Queue::assertNotPushed(DropCaches::class);
    }
}
