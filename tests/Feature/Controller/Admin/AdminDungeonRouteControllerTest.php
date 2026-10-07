<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\GeneratesDungeonRoutes;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
final class AdminDungeonRouteControllerTest extends PublicTestCase
{
    use GeneratesDungeonRoutes;

    private DungeonRoute $dungeonRoute;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));

        $this->dungeonRoute = DungeonRoute::factory()->create([
            'author_id'          => 1,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            $this->dungeonRoute->delete();
        } catch (\Throwable) {
            // Route may have already been deleted in a test
        }

        parent::tearDown();
    }

    #[Test]
    public function index_givenAuthenticatedAdmin_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.dungeonroutes'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function index_givenDungeonFilter_returnsOnlyMatchingDungeon(): void
    {
        // Arrange
        $otherRoute = DungeonRoute::factory()->create([
            'author_id'  => 1,
            'dungeon_id' => Dungeon::query()->whereKeyNot($this->dungeonRoute->dungeon_id)->firstOrFail()->id,
        ]);

        try {
            // Act
            $response = $this->get(route('admin.dungeonroutes', [
                'dungeon_id' => $this->dungeonRoute->dungeon_id,
            ]));

            // Assert
            $response->assertOk();
            $response->assertSee($this->dungeonRoute->public_key);
            $response->assertDontSee($otherRoute->public_key);
        } finally {
            $otherRoute->delete();
        }
    }

    #[Test]
    public function index_givenAuthorFilter_returnsOnlyMatchingAuthorRoute(): void
    {
        // Arrange
        $otherUser  = User::where('id', '!=', 1)->firstOrFail();
        $otherRoute = DungeonRoute::factory()->create(['author_id' => $otherUser->id]);

        try {
            // Act
            $response = $this->get(route('admin.dungeonroutes', [
                'author' => User::findOrFail(1)->name,
            ]));

            // Assert
            $response->assertOk();
            $response->assertSee($this->dungeonRoute->public_key);
            $response->assertDontSee($otherRoute->public_key);
        } finally {
            $otherRoute->delete();
        }
    }

    #[Test]
    public function index_givenPublishedStateFilter_returnsFilteredResults(): void
    {
        // Arrange
        $unpublishedRoute = DungeonRoute::factory()->create([
            'author_id'          => 1,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);

        try {
            // Act
            $response = $this->get(route('admin.dungeonroutes', [
                'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            ]));

            // Assert
            $response->assertOk();
            $response->assertSee($this->dungeonRoute->public_key);
            $response->assertDontSee($unpublishedRoute->public_key);
        } finally {
            $unpublishedRoute->delete();
        }
    }

    #[Test]
    public function index_givenPublicKeyFilter_returnsOnlyThatRoute(): void
    {
        // Arrange
        $otherRoute = DungeonRoute::factory()->create(['author_id' => 1]);

        try {
            // Act
            $response = $this->get(route('admin.dungeonroutes', [
                'public_key' => $this->dungeonRoute->public_key,
            ]));

            // Assert
            $response->assertOk();
            $response->assertViewHas('models', fn($models) => $models->pluck('id')->all() === [$this->dungeonRoute->id]);
        } finally {
            $otherRoute->delete();
        }
    }

    #[Test]
    public function index_givenWorldPublishedRoute_rendersItsPublishedStateByKey(): void
    {
        // Arrange
        $this->dungeonRoute->update(['published_state_id' => PublishedState::ALL[PublishedState::WORLD]]);

        // Act
        $response = $this->get(route('admin.dungeonroutes', [
            'public_key' => $this->dungeonRoute->public_key,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('publishedStates', static fn($publishedStates) => $publishedStates->all() === array_flip(PublishedState::ALL));
        $response->assertSee('fa-globe');
        $response->assertSee(__('js.publish_state_title_world'));
    }

    #[Test]
    public function index_givenNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));
        $this->be($user);

        try {
            // Act
            $response = $this->get(route('admin.dungeonroutes'));

            // Assert
            $response->assertForbidden();
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function edit_givenExistingRoute_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.dungeonroute.edit', ['dungeonRoute' => $this->dungeonRoute->id]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('dungeonRoute', fn(DungeonRoute $dungeonRoute) => $dungeonRoute->id === $this->dungeonRoute->id);
        $response->assertSee($this->dungeonRoute->public_key);
        $response->assertViewHas('publishedStates', static fn($publishedStates) => $publishedStates->all() === array_flip(PublishedState::ALL));
    }

    #[Test]
    public function update_givenValidPublishedState_updatesAndRedirects(): void
    {
        // Arrange
        $newStateId = PublishedState::ALL[PublishedState::UNPUBLISHED];

        // Act
        $response = $this->patch(route('admin.dungeonroute.update', ['dungeonRoute' => $this->dungeonRoute->id]), [
            'published_state_id' => $newStateId,
        ]);

        // Assert
        $response->assertRedirect(route('admin.dungeonroute.edit', ['dungeonRoute' => $this->dungeonRoute->id]));
        $this->assertEquals($newStateId, $this->dungeonRoute->fresh()->published_state_id);
    }

    #[Test]
    public function update_givenInvalidPublishedState_redirectsWithValidationError(): void
    {
        // Arrange

        // Act
        $response = $this->patch(route('admin.dungeonroute.update', ['dungeonRoute' => $this->dungeonRoute->id]), [
            'published_state_id' => 9999,
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors('published_state_id');
        $this->assertEquals(PublishedState::ALL[PublishedState::WORLD], $this->dungeonRoute->fresh()->published_state_id);
    }

    #[Test]
    public function destroy_givenExistingRoute_deletesAndRedirects(): void
    {
        // Arrange
        $route = DungeonRoute::factory()->create(['author_id' => 1]);

        try {
            // Act
            $response = $this->delete(route('admin.dungeonroute.delete', ['dungeonRoute' => $route->id]));

            // Assert
            $response->assertRedirect(route('admin.dungeonroutes'));
            $this->assertNull(DungeonRoute::find($route->id));
        } finally {
            $route->exists && $route->delete();
        }
    }

    #[Test]
    public function claim_givenExistingRoute_setsAuthorToCurrentUser(): void
    {
        // Arrange
        $adminUser                     = User::findOrFail(1);
        $this->dungeonRoute->author_id = 2;
        $this->dungeonRoute->save();

        // Act
        $response = $this->post(route('admin.dungeonroute.claim', ['dungeonRoute' => $this->dungeonRoute->id]));

        // Assert
        $response->assertRedirect(route('admin.dungeonroute.edit', ['dungeonRoute' => $this->dungeonRoute->id]));
        $this->assertEquals($adminUser->id, $this->dungeonRoute->fresh()->author_id);
    }
}
