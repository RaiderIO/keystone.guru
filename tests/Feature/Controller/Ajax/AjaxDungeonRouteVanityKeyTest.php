<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonBenefit;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\GrantsPatreonBenefits;
use Tests\TestCases\AjaxPublicTestCase;

/**
 * The route settings form saves a route's custom URL (its vanity key) through
 * `PATCH /ajax/{dungeonRoute}`, and only for whoever holds the custom URLs Patreon benefit.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteVanityKeyTest extends AjaxPublicTestCase
{
    use GrantsPatreonBenefits;

    private const string VANITY_KEY = 'my-cool-route';

    #[Test]
    public function store_givenVanityKeyFromEntitledUser_storesIt(): void
    {
        // Arrange
        Queue::fake();
        $owner = $this->createRouteAuthor(entitled: true);
        $route = $this->createRoute($owner, null);

        try {
            // Act
            $response = $this->actingAs($owner)->patch($this->updateUrl($route), [
                'dungeon_route_vanity_key' => self::VANITY_KEY,
            ]);

            // Assert
            $response->assertOk();
            $this->assertSame(self::VANITY_KEY, $route->refresh()->vanity_key);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function store_givenVanityKeyFromUnentitledUser_returnsValidationErrorAndLeavesTheRouteUntouched(): void
    {
        // Arrange
        Queue::fake();
        $owner = $this->createRouteAuthor(entitled: false);
        $route = $this->createRoute($owner, null);

        try {
            // Act
            $response = $this->actingAs($owner)->patch($this->updateUrl($route), [
                'dungeon_route_vanity_key' => self::VANITY_KEY,
            ]);

            // Assert
            $response->assertStatus(302);
            $response->assertSessionHasErrors('dungeon_route_vanity_key');
            $this->assertNull($route->refresh()->vanity_key);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function store_givenVanityKeyTakenByAnotherRoute_returnsValidationError(): void
    {
        // Arrange
        Queue::fake();
        $owner      = $this->createRouteAuthor(entitled: true);
        $route      = $this->createRoute($owner, null);
        $otherRoute = $this->createRoute($owner, self::VANITY_KEY);

        try {
            // Act
            $response = $this->actingAs($owner)->patch($this->updateUrl($route), [
                'dungeon_route_vanity_key' => self::VANITY_KEY,
            ]);

            // Assert
            $response->assertSessionHasErrors('dungeon_route_vanity_key');
            $this->assertNull($route->refresh()->vanity_key);
        } finally {
            $otherRoute->delete();
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function store_givenItsOwnVanityKeyAgain_keepsIt(): void
    {
        // Arrange
        Queue::fake();
        $owner = $this->createRouteAuthor(entitled: true);
        $route = $this->createRoute($owner, self::VANITY_KEY);

        try {
            // Act
            $response = $this->actingAs($owner)->patch($this->updateUrl($route), [
                'dungeon_route_vanity_key' => self::VANITY_KEY,
            ]);

            // Assert
            $response->assertOk();
            $this->assertSame(self::VANITY_KEY, $route->refresh()->vanity_key);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function store_givenNoVanityKeyField_leavesTheExistingOneAlone(): void
    {
        // Arrange
        Queue::fake();
        $owner = $this->createRouteAuthor(entitled: true);
        $route = $this->createRoute($owner, self::VANITY_KEY);

        try {
            // Act
            $response = $this->actingAs($owner)->patch($this->updateUrl($route), [
                'dungeon_route_title' => 'A new title',
            ]);

            // Assert
            $response->assertOk();
            $this->assertSame(self::VANITY_KEY, $route->refresh()->vanity_key);
            $this->assertSame('A new title', $route->title);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function store_givenEmptyVanityKey_clearsItAgain(): void
    {
        // Arrange
        Queue::fake();
        $owner = $this->createRouteAuthor(entitled: true);
        $route = $this->createRoute($owner, self::VANITY_KEY);

        try {
            // Act
            $response = $this->actingAs($owner)->patch($this->updateUrl($route), [
                'dungeon_route_vanity_key' => '',
            ]);

            // Assert
            $response->assertOk();
            $this->assertNull($route->refresh()->vanity_key);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    private function createRouteAuthor(bool $entitled): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));

        if ($entitled) {
            $this->grantPatreonBenefit($user, PatreonBenefit::CUSTOM_URLS);
        }

        return $user;
    }

    private function createRoute(User $owner, ?string $vanityKey): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'vanity_key'         => $vanityKey,
        ]);
    }

    private function updateUrl(DungeonRoute $route): string
    {
        return route('api.dungeonroute.update', ['dungeonRoute' => $route->public_key]);
    }
}
