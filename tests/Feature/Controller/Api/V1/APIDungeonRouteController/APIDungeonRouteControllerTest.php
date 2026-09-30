<?php

namespace Tests\Feature\Controller\Api\V1\APIDungeonRouteController;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use App\Service\Controller\Api\V1\APIDungeonRouteControllerServiceInterface;
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
            $this->assertSame([$dungeonRoute->public_key], array_column($response->json('data'), 'publicKey'));
            $this->assertTrue($dungeonRoute->refresh()->last_accessed_at?->isToday() ?? false);
        } finally {
            $dungeonRoute?->delete();
            $user?->delete();
        }
    }

    #[Test]
    public function index_givenRoutesOfOtherUsers_returnsOnlyOwnRoutes(): void
    {
        // Arrange
        Queue::fake();
        $user         = null;
        $ownRoute     = null;
        $foreignRoute = null;

        try {
            $user = User::factory()->create();
            $user->addRole(Role::firstWhere('name', Role::ROLE_USER));
            $ownRoute = DungeonRoute::factory()->create([
                'author_id'  => $user->id,
                'expires_at' => null,
            ]);
            $foreignRoute = DungeonRoute::factory()->create([
                'author_id'  => 1,
                'expires_at' => null,
            ]);

            // Act
            $response = $this->actingAs($user)->getJson(route('api.v1.route.index'));

            // Assert
            $response->assertOk();
            $this->assertSame([$ownRoute->public_key], array_column($response->json('data'), 'publicKey'));
            $this->assertNull($foreignRoute->refresh()->last_accessed_at);
        } finally {
            $foreignRoute?->delete();
            $ownRoute?->delete();
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
            $response->assertJsonPath('data.publicKey', $dungeonRoute->public_key);
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

    #[Test]
    public function storeThumbnails_givenViewportWidthBelowMinimum_returnsUnprocessable(): void
    {
        // Arrange
        Queue::fake();
        $dungeonRoute = null;

        try {
            $dungeonRoute = DungeonRoute::factory()->create([
                'author_id'  => 1,
                'expires_at' => null,
            ]);
            $apiDungeonRouteControllerService = $this->createMockPublic(APIDungeonRouteControllerServiceInterface::class);
            $apiDungeonRouteControllerService->expects($this->never())->method('createThumbnails');
            app()->instance(APIDungeonRouteControllerServiceInterface::class, $apiDungeonRouteControllerService);

            // Act
            $response = $this->postJson(route('api.v1.route.thumbnail.store', ['dungeonRoute' => $dungeonRoute]), [
                'viewport_width' => 100,
            ]);

            // Assert
            $response->assertUnprocessable();
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function storeThumbnails_givenValidDimensions_queuesThumbnailsForTheRoute(): void
    {
        // Arrange
        Queue::fake();
        $dungeonRoute = null;

        try {
            $dungeonRoute = DungeonRoute::factory()->create([
                'author_id'  => 1,
                'expires_at' => null,
            ]);
            $apiDungeonRouteControllerService = $this->createMockPublic(APIDungeonRouteControllerServiceInterface::class);
            $apiDungeonRouteControllerService->expects($this->once())
                ->method('createThumbnails')
                ->with($this->callback(static fn(DungeonRoute $passed): bool => $passed->id === $dungeonRoute->id))
                ->willReturn(collect());
            app()->instance(APIDungeonRouteControllerServiceInterface::class, $apiDungeonRouteControllerService);

            // Act
            $response = $this->postJson(route('api.v1.route.thumbnail.store', ['dungeonRoute' => $dungeonRoute]), [
                'viewport_width'  => 800,
                'viewport_height' => 600,
                'image_width'     => 400,
                'image_height'    => 300,
                'zoom_level'      => 2.5,
                'quality'         => 90,
            ]);

            // Assert
            $response->assertSuccessful();
            $response->assertJsonPath('data', []);
        } finally {
            $dungeonRoute?->delete();
        }
    }
}
