<?php

namespace Tests\Feature\View\Common\Maps;

use App\Features\WalkGraphLayer;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\PublishedState;
use App\Models\User;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * The map JS only adds the walk graph map object group when the page hands it `walkGraphLayer: true`: pages that
 * opted in, with the feature active.
 */
#[Group('View')]
#[Group('WalkGraph')]
final class MapWalkGraphLayerOptionTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const string OPTION_ON  = '"walkGraphLayer":true';
    private const string OPTION_OFF = '"walkGraphLayer":false';

    #[Test]
    public function explore_givenFeatureActive_offersTheWalkGraphLayer(): void
    {
        // Arrange
        $user = User::factory()->create();
        Feature::for($user)->activate(WalkGraphLayer::class);

        try {
            // Act
            $response = $this->actingAs($user)->get($this->exploreUrl());

            // Assert
            $response->assertOk();
            $response->assertSee(self::OPTION_ON, false);
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
    public function view_givenFeatureActive_doesNotOfferTheWalkGraphLayer(): void
    {
        // Arrange - a route page did not opt in
        $owner = User::factory()->create();
        $route = DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
        Feature::for($owner)->activate(WalkGraphLayer::class);

        try {
            // Act
            $response = $this->actingAs($owner)->followingRedirects()->get(route('dungeonroute.view', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]));

            // Assert
            $response->assertOk();
            $response->assertSee(self::OPTION_OFF, false);
        } finally {
            Feature::for($owner)->forget(WalkGraphLayer::class);
            $route->delete();
            $owner->delete();
        }
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
