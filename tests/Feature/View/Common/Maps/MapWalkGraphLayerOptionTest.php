<?php

namespace Tests\Feature\View\Common\Maps;

use App\Features\WalkGraphLayer;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Laratrust\Role;
use App\Models\LiveSession;
use App\Models\PublishedState;
use App\Models\User;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * The map JS only adds the walk graph map object group when the page hands it a `walkGraphLayerStyle`: pages that
 * opted in, with the feature active. Users get the walkable area outline on the explore and route pages, the admin
 * mapping editor every segment.
 */
#[Group('View')]
#[Group('WalkGraph')]
final class MapWalkGraphLayerOptionTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const string OPTION_AREA     = '"walkGraphLayerStyle":"area"';
    private const string OPTION_SEGMENTS = '"walkGraphLayerStyle":"segments"';
    private const string OPTION_OFF      = '"walkGraphLayerStyle":null';

    #[Test]
    public function explore_givenFeatureActive_offersTheWalkableAreaOutline(): void
    {
        // Arrange
        $user = User::factory()->create();
        Feature::for($user)->activate(WalkGraphLayer::class);

        try {
            // Act
            $response = $this->actingAs($user)->get($this->exploreUrl());

            // Assert
            $response->assertOk();
            $response->assertSee(self::OPTION_AREA, false);
        } finally {
            Feature::for($user)->forget(WalkGraphLayer::class);
            $user->delete();
        }
    }

    #[Test]
    public function explore_givenFeatureInactive_doesNotOfferTheWalkGraphLayer(): void
    {
        // Arrange
        $user = User::factory()->create();
        Feature::for($user)->deactivate(WalkGraphLayer::class);

        try {
            // Act
            $response = $this->actingAs($user)->get($this->exploreUrl());

            // Assert
            $response->assertOk();
            $response->assertSee(self::OPTION_OFF, false);
        } finally {
            Feature::for($user)->forget(WalkGraphLayer::class);
            $user->delete();
        }
    }

    #[Test]
    #[DataProvider('routePageProvider')]
    public function routePage_givenFeatureActive_offersTheWalkableAreaOutline(string $routeName): void
    {
        // Arrange
        [$owner, $route, $liveSession] = $this->createRouteWithLiveSession();
        Feature::for($owner)->activate(WalkGraphLayer::class);

        try {
            // Act
            $response = $this->actingAs($owner)->followingRedirects()->get($this->routePageUrl($routeName, $route, $liveSession));

            // Assert
            $response->assertOk();
            $response->assertSee(self::OPTION_AREA, false);
        } finally {
            Feature::for($owner)->forget(WalkGraphLayer::class);
            $this->deleteRouteWithLiveSession($owner, $route, $liveSession);
        }
    }

    #[Test]
    #[DataProvider('routePageProvider')]
    public function routePage_givenFeatureInactive_doesNotOfferTheWalkGraphLayer(string $routeName): void
    {
        // Arrange
        [$owner, $route, $liveSession] = $this->createRouteWithLiveSession();
        Feature::for($owner)->deactivate(WalkGraphLayer::class);

        try {
            // Act
            $response = $this->actingAs($owner)->followingRedirects()->get($this->routePageUrl($routeName, $route, $liveSession));

            // Assert
            $response->assertOk();
            $response->assertSee(self::OPTION_OFF, false);
        } finally {
            Feature::for($owner)->forget(WalkGraphLayer::class);
            $this->deleteRouteWithLiveSession($owner, $route, $liveSession);
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function routePageProvider(): array
    {
        return [
            'edit'         => ['dungeonroute.edit'],
            'view'         => ['dungeonroute.view'],
            'live session' => ['dungeonroute.livesession.view'],
        ];
    }

    #[Test]
    public function embed_givenFeatureActive_doesNotOfferTheWalkGraphLayer(): void
    {
        // Arrange - an embed has no map object group visibility dropdown to toggle the layer with
        [$owner, $route, $liveSession] = $this->createRouteWithLiveSession();
        Feature::for($owner)->activate(WalkGraphLayer::class);

        try {
            // Act
            $response = $this->actingAs($owner)->followingRedirects()->get($this->routePageUrl('dungeonroute.embed', $route, $liveSession));

            // Assert
            $response->assertOk();
            $response->assertSee(self::OPTION_OFF, false);
        } finally {
            Feature::for($owner)->forget(WalkGraphLayer::class);
            $this->deleteRouteWithLiveSession($owner, $route, $liveSession);
        }
    }

    #[Test]
    public function mapping_givenFeatureActive_offersEveryWalkedSegment(): void
    {
        // Arrange
        $admin = User::findOrFail(1);
        Feature::for($admin)->activate(WalkGraphLayer::class);
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, dungeonActive: true, requireDefaultFloor: true);

        try {
            // Act
            $response = $this->actingAs($admin)->get(route('admin.floor.edit.mapping', [
                'dungeon'         => $dungeon,
                'floor'           => $dungeon->floors()->firstOrFail(),
                'mapping_version' => $mappingVersion,
            ]));

            // Assert
            $response->assertOk();
            $response->assertSee(self::OPTION_SEGMENTS, false);
        } finally {
            Feature::for($admin)->forget(WalkGraphLayer::class);
        }
    }

    /**
     * @return array{User, DungeonRoute, LiveSession}
     */
    private function createRouteWithLiveSession(): array
    {
        $owner = User::factory()->create();
        $owner->addRole(Role::firstWhere('name', Role::ROLE_USER));
        $route = DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
        $liveSession = LiveSession::create([
            'dungeon_route_id' => $route->id,
            'user_id'          => $owner->id,
            'public_key'       => LiveSession::generateRandomPublicKey(),
        ]);

        return [$owner, $route, $liveSession];
    }

    private function deleteRouteWithLiveSession(User $owner, DungeonRoute $route, LiveSession $liveSession): void
    {
        // Mass delete on purpose: LiveSession's "deleting" hook cascades into overpulled_enemies
        LiveSession::query()->whereKey($liveSession->id)->delete();
        $route->delete();
        $owner->delete();
    }

    private function routePageUrl(string $routeName, DungeonRoute $route, LiveSession $liveSession): string
    {
        return route($routeName, [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
            'title'        => $route->getTitleSlug(),
            'liveSession'  => $liveSession,
        ]);
    }

    private function exploreUrl(): string
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, dungeonActive: true, requireDefaultFloor: true);
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        return route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $mappingVersion->gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
        ]);
    }
}
