<?php

namespace Tests\Unit\App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
final class DungeonRouteCollectionTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('publishedStateProvider')]
    public function mayUserView_givenPublishedState_returnsExpectedResultForAnotherUser(
        string $publishedState,
        bool   $expected,
        string $because,
    ): void {
        // Arrange
        $owner  = User::factory()->create();
        $viewer = User::factory()->create();

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $owner->id,
            'published_state_id' => PublishedState::ALL[$publishedState],
        ]);

        try {
            // Act
            $result = $dungeonRouteCollection->mayUserView($viewer);

            // Assert
            $this->assertSame($expected, $result, $because);
        } finally {
            $dungeonRouteCollection->delete();
            $viewer->delete();
            $owner->delete();
        }
    }

    /** @return array<string, array{0: string, 1: bool, 2: string}> */
    public static function publishedStateProvider(): array
    {
        return [
            'unpublished' => [
                PublishedState::UNPUBLISHED, false,
                'An unpublished collection is for its owner only',
            ],
            'team without a team' => [
                PublishedState::TEAM, false,
                'A team published collection without a team is visible to nobody but its owner',
            ],
            'world with link' => [
                PublishedState::WORLD_WITH_LINK, true,
                'Anyone holding the link may view the collection',
            ],
            'world' => [
                PublishedState::WORLD, true,
                'A world published collection is public',
            ],
        ];
    }

    #[Test]
    public function mayUserView_givenAnUnpublishedCollection_returnsTrueForItsOwner(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $owner->id,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);

        try {
            // Act
            $result = $dungeonRouteCollection->mayUserView($owner);

            // Assert
            $this->assertTrue($result, 'The owner may always view their own collection');
        } finally {
            $dungeonRouteCollection->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function mayUserView_givenAWorldPublishedCollection_returnsTrueForAGuest(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $owner->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);

        try {
            // Act
            $result = $dungeonRouteCollection->mayUserView(null);

            // Assert
            $this->assertTrue($result, 'A guest may view a world published collection');
        } finally {
            $dungeonRouteCollection->delete();
            $owner->delete();
        }
    }

    /**
     * This project uses no foreign keys, so a deleted route would otherwise leave a coupling
     * behind that renders as a hole in the collection.
     */
    #[Test]
    public function delete_givenARouteInsideACollection_removesItsCoupling(): void
    {
        // Arrange
        $owner        = User::factory()->create();
        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'  => $owner->id,
            'expires_at' => null,
        ]);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $owner->id]);
        DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'dungeon_route_id'            => $dungeonRoute->id,
            'order'                       => 0,
        ]);

        try {
            // Act
            $dungeonRoute->delete();

            // Assert
            $this->assertSame(
                0,
                DungeonRouteCollectionRoute::where('dungeon_route_id', $dungeonRoute->id)->count(),
                'Deleting a route must clean up the collections it was in',
            );
        } finally {
            $dungeonRouteCollection->delete();
            $owner->delete();
        }
    }
}
