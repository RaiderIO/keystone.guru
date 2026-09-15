<?php

namespace Tests\Feature\Controller\Api\V1\APIDungeonRouteController;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\APIPublicTestCase;

#[Group('Controller')]
#[Group('API')]
#[Group('APIDungeonRoute')]
final class APIDungeonRouteControllerTest extends APIPublicTestCase
{
    #[Test]
    public function index_givenOwnRoute_stampsItAsAccessed(): void
    {
        // Arrange
        Queue::fake();
        $user         = null;
        $dungeonRoute = null;

        try {
            $user = User::factory()->create();
            $user->addRole(Role::firstWhere('name', Role::ROLE_USER));
            $dungeonRoute = DungeonRoute::factory()->create([
                'author_id'  => $user->id,
                'expires_at' => null,
            ]);

            // Act
            $response = $this->actingAs($user)->getJson(route('api.v1.route.index'));

            // Assert
            $response->assertOk();
            $this->assertTrue($dungeonRoute->refresh()->last_accessed_at?->isToday() ?? false);
        } finally {
            $dungeonRoute?->delete();
            $user?->delete();
        }
    }

    #[Test]
    public function show_givenViewableRoute_stampsItAsAccessed(): void
    {
        // Arrange
        Queue::fake();
        $dungeonRoute = null;

        try {
            $dungeonRoute = DungeonRoute::factory()->create([
                'author_id'  => 1,
                'expires_at' => null,
            ]);

            // Act
            $response = $this->getJson(route('api.v1.route.show', ['dungeonRoute' => $dungeonRoute]));

            // Assert
            $response->assertOk();
            $this->assertTrue($dungeonRoute->refresh()->last_accessed_at?->isToday() ?? false);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function show_givenRouteTheUserMayNotView_doesNotStampIt(): void
    {
        // Arrange
        Queue::fake();
        $owner        = null;
        $viewer       = null;
        $dungeonRoute = null;

        try {
            $owner        = User::factory()->create();
            $dungeonRoute = DungeonRoute::factory()->create([
                'author_id'          => $owner->id,
                'expires_at'         => null,
                'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            ]);
            $viewer = User::factory()->create();
            $viewer->addRole(Role::firstWhere('name', Role::ROLE_USER));

            // Act
            $response = $this->actingAs($viewer)->getJson(route('api.v1.route.show', ['dungeonRoute' => $dungeonRoute]));

            // Assert
            $response->assertForbidden();
            $this->assertNull($dungeonRoute->refresh()->last_accessed_at);
        } finally {
            $dungeonRoute?->delete();
            $viewer?->delete();
            $owner?->delete();
        }
    }
}
