<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteControllerCopyTest extends PublicTestCase
{
    private DungeonRoute $sourceRoute;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceRoute = DungeonRoute::factory()->create([
            'author_id'          => 1,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            DungeonRoute::query()->where('clone_of', $this->sourceRoute->public_key)->get()->each->delete();

            $this->sourceRoute->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function copy_givenAGuest_redirectsToLoginAndClonesNothing(): void
    {
        // Act
        $response = $this->get($this->cloneUrl());

        // Assert
        $response->assertRedirect(route('login'));
        $this->assertSame(0, DungeonRoute::query()->where('clone_of', $this->sourceRoute->public_key)->count());
    }

    #[Test]
    public function copy_givenAUserAtTheirRouteLimit_showsTheLimitReachedPageAndClonesNothing(): void
    {
        // Arrange
        $user = $this->createUserWithRoutes(1);
        config(['keystoneguru.registered_user_dungeonroute_limit' => 1]);

        try {
            // Act
            $response = $this->actingAs($user)->get($this->cloneUrl());

            // Assert
            $response->assertOk();
            $response->assertViewIs('dungeonroute.limitreached');
            $this->assertSame(0, DungeonRoute::query()->where('clone_of', $this->sourceRoute->public_key)->count());
        } finally {
            DungeonRoute::query()->where('author_id', $user->id)->get()->each->delete();
            $user->delete();
        }
    }

    #[Test]
    public function copy_givenAUserUnderTheirRouteLimit_clonesTheRouteForThatUser(): void
    {
        // Arrange
        $user = $this->createUserWithRoutes(1);
        config(['keystoneguru.registered_user_dungeonroute_limit' => 2]);

        try {
            // Act
            $response = $this->actingAs($user)->get($this->cloneUrl());

            // Assert
            $this->assertSame(1, DungeonRoute::query()->where('clone_of', $this->sourceRoute->public_key)->count());

            /** @var DungeonRoute $clone */
            $clone = DungeonRoute::query()->where('clone_of', $this->sourceRoute->public_key)->firstOrFail();
            $response->assertRedirect(route('dungeonroute.edit', [
                'dungeon'      => $clone->dungeon,
                'dungeonroute' => $clone,
                'title'        => $clone->title,
            ]));
            $this->assertSame($user->id, $clone->author_id);
        } finally {
            DungeonRoute::query()->where('author_id', $user->id)->get()->each->delete();
            $user->delete();
        }
    }

    private function createUserWithRoutes(int $routeCount): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        DungeonRoute::factory()->count($routeCount)->create(['author_id' => $user->id, 'expires_at' => null]);

        return $user;
    }

    private function cloneUrl(): string
    {
        return route('dungeonroute.clone', [
            'dungeon'      => $this->sourceRoute->dungeon,
            'dungeonroute' => $this->sourceRoute,
            'title'        => $this->sourceRoute->getTitleSlug(),
        ]);
    }
}
