<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Events\Models\Arrow\ArrowChangedEvent;
use App\Models\Arrow;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonBenefit;
use App\Models\PublishedState;
use App\Models\User;
use App\Service\Floor\FloorResolutionServiceInterface;
use App\Service\MapContext\MapContextServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\GrantsPatreonBenefits;
use Tests\TestCases\PublicTestCase;

/**
 * A route's custom URL (its vanity key) replaces the public key in the route's URL. The public key
 * keeps resolving the route, so links shared before the custom URL was set stay alive.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteVanityKeyTest extends PublicTestCase
{
    use GrantsPatreonBenefits;

    private const string VANITY_KEY = 'my-cool-route';

    #[Test]
    public function getRouteKey_givenVanityKey_returnsItInsteadOfThePublicKey(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, self::VANITY_KEY);

        try {
            // Act
            $routeKey = $route->getRouteKey();

            // Assert
            $this->assertSame(self::VANITY_KEY, $routeKey);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function resolveRouteBinding_givenVanityKey_resolvesTheRoute(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, self::VANITY_KEY);

        try {
            // Act
            $resolved = new DungeonRoute()->resolveRouteBinding(self::VANITY_KEY);

            // Assert
            $this->assertNotNull($resolved);
            $this->assertSame($route->id, $resolved->id);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function resolveRouteBinding_givenPublicKeyWhileVanityKeyIsSet_resolvesTheRoute(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, self::VANITY_KEY);

        try {
            // Act
            $resolved = new DungeonRoute()->resolveRouteBinding($route->public_key);

            // Assert
            $this->assertNotNull($resolved);
            $this->assertSame($route->id, $resolved->id);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function resolveRouteBinding_givenIdField_resolvesTheRouteById(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, self::VANITY_KEY);

        try {
            // Act
            $resolved = new DungeonRoute()->resolveRouteBinding($route->id, 'id');

            // Assert
            $this->assertNotNull($resolved);
            $this->assertSame($route->id, $resolved->id);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function viewFloor_givenVanityKeyUrl_returnsOk(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, self::VANITY_KEY);

        try {
            // Act
            $response = $this->get($this->viewFloorUrl($route, self::VANITY_KEY));

            // Assert
            $response->assertOk();
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function viewFloor_givenPublicKeyUrlWhileVanityKeyIsSet_redirectsToTheCustomUrl(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, self::VANITY_KEY);

        try {
            // Act
            $response = $this->get($this->viewFloorUrl($route, $route->public_key));

            // Assert
            $response->assertStatus(301);
            $response->assertRedirect($this->viewFloorUrl($route, self::VANITY_KEY));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function viewFloor_givenPublicKeyUrlWithoutVanityKey_returnsOk(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, null);

        try {
            // Act
            $response = $this->get($this->viewFloorUrl($route, $route->public_key));

            // Assert
            $response->assertOk();
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    /**
     * A presence channel is authorized once, when the client joins it. Naming it after a key that can be
     * changed - or released and claimed by another route - would leave those clients listening to a name
     * that has come to mean a different route.
     */
    #[Test]
    public function getEchoChannelName_givenVanityKey_staysOnThePublicKey(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, self::VANITY_KEY);

        try {
            // Act
            $channelName = app(MapContextServiceInterface::class)
                ->createMapContextDungeonRoute($route, User::MAP_FACADE_STYLE_SPLIT_FLOORS)
                ->getEchoChannelName();

            // Assert
            $this->assertStringEndsWith(sprintf('-route-edit.%s', $route->public_key), $channelName);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function broadcastOn_givenVanityKey_staysOnThePublicKeyChannel(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, self::VANITY_KEY);

        try {
            // Act
            $channels = new ArrowChangedEvent($route, $owner, new Arrow())->broadcastOn();

            // Assert
            $this->assertCount(1, $channels);
            $this->assertStringEndsWith(sprintf('-route-edit.%s', $route->public_key), $channels[0]->name);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function edit_givenEntitledOwner_rendersTheCustomUrlField(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $owner->addRole(Role::firstWhere('name', Role::ROLE_USER));
        $this->grantPatreonBenefit($owner, PatreonBenefit::CUSTOM_URLS);
        $route = $this->createRoute($owner, null);

        try {
            // Act
            $response = $this->actingAs($owner)->followingRedirects()->get($this->editUrl($route));

            // Assert
            $response->assertOk();
            $response->assertSee(__('view_common.forms.createroute.vanity_key'));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function edit_givenUnentitledOwner_hidesTheCustomUrlField(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $owner->addRole(Role::firstWhere('name', Role::ROLE_USER));
        $route = $this->createRoute($owner, null);

        try {
            // Act
            $response = $this->actingAs($owner)->followingRedirects()->get($this->editUrl($route));

            // Assert
            $response->assertOk();
            $response->assertDontSee(__('view_common.forms.createroute.vanity_key'));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    /**
     * A published, non-sandbox route: sandbox routes (the factory default) are editable by anyone.
     */
    private function createRoute(User $owner, ?string $vanityKey): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'vanity_key'         => $vanityKey,
        ]);
    }

    private function editUrl(DungeonRoute $route): string
    {
        return route('dungeonroute.edit', [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
            'title'        => $route->getTitleSlug(),
        ]);
    }

    private function viewFloorUrl(DungeonRoute $route, string $key): string
    {
        $defaultFloor = app(FloorResolutionServiceInterface::class)
            ->resolveDefaultFloor($route->dungeon, $route->mappingVersion);

        return route('dungeonroute.view.floor', [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $key,
            'title'        => $route->getTitleSlug(),
            'floorIndex'   => $defaultFloor->index,
        ]);
    }
}
