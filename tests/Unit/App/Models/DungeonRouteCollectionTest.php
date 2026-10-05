<?php

namespace Tests\Unit\App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use App\Models\UserPinnedDungeonRouteCollection;
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

    #[Test]
    public function mayUserView_givenATeamPublishedCollection_returnsTrueForATeamMember(): void
    {
        // Arrange
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $team   = $this->createTeam();
        TeamUser::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role'    => TeamUser::ROLE_MEMBER,
        ]);
        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $owner->id,
            'team_id'            => $team->id,
            'published_state_id' => PublishedState::ALL[PublishedState::TEAM],
        ]);

        try {
            // Act
            $result = $dungeonRouteCollection->mayUserView($member);

            // Assert
            $this->assertTrue($result, 'A member of the team may view a team published collection');
        } finally {
            $dungeonRouteCollection->delete();
            $team->delete();
            $member->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function mayUserView_givenATeamPublishedCollection_returnsFalseForAUserOutsideTheTeam(): void
    {
        // Arrange
        $owner    = User::factory()->create();
        $member   = User::factory()->create();
        $outsider = User::factory()->create();
        $team     = $this->createTeam();
        TeamUser::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role'    => TeamUser::ROLE_MEMBER,
        ]);
        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $owner->id,
            'team_id'            => $team->id,
            'published_state_id' => PublishedState::ALL[PublishedState::TEAM],
        ]);

        try {
            // Act
            $result = $dungeonRouteCollection->mayUserView($outsider);

            // Assert
            $this->assertFalse($result, 'Only members of the team may view a team published collection');
        } finally {
            $dungeonRouteCollection->delete();
            $team->delete();
            $outsider->delete();
            $member->delete();
            $owner->delete();
        }
    }

    #[Test]
    #[DataProvider('adminOnlyPublishedStateProvider')]
    public function mayUserView_givenACollectionHiddenFromOtherUsers_returnsTrueForAnAdmin(string $publishedState): void
    {
        // Arrange
        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');
        $owner                  = User::factory()->create();
        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $owner->id,
            'published_state_id' => PublishedState::ALL[$publishedState],
        ]);

        try {
            // Act
            $result = $dungeonRouteCollection->mayUserView($admin);

            // Assert
            $this->assertTrue($result, 'An admin may view any collection');
        } finally {
            $dungeonRouteCollection->delete();
            $owner->delete();
        }
    }

    /** @return array<string, array{0: string}> */
    public static function adminOnlyPublishedStateProvider(): array
    {
        return [
            'unpublished'         => [PublishedState::UNPUBLISHED],
            'team without a team' => [PublishedState::TEAM],
        ];
    }

    /**
     * This project uses no foreign keys, so a deleted collection would otherwise leave its route
     * couplings behind.
     */
    #[Test]
    public function delete_givenACollectionHoldingARoute_removesItsCoupling(): void
    {
        // Arrange
        $owner        = User::factory()->create();
        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'  => $owner->id,
            'expires_at' => null,
        ]);
        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $owner->id]);
        $coupling               = DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'dungeon_route_id'            => $dungeonRoute->id,
            'order'                       => 0,
        ]);

        try {
            // Act
            $dungeonRouteCollection->delete();

            // Assert
            $this->assertFalse(
                DungeonRouteCollectionRoute::whereKey($coupling->id)->exists(),
                'Deleting a collection must clean up the couplings to its routes',
            );
            $this->assertTrue(DungeonRoute::whereKey($dungeonRoute->id)->exists(), 'The route itself stays');
        } finally {
            DungeonRouteCollectionRoute::whereKey($coupling->id)->delete();
            $dungeonRoute->delete();
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

    /**
     * This project uses no foreign keys, so a deleted collection would otherwise leave a dangling
     * pin behind on whoever pinned it to their creator podium.
     */
    #[Test]
    public function delete_givenAPinnedCollection_removesThePin(): void
    {
        // Arrange
        $owner  = User::factory()->create();
        $pinner = User::factory()->create();

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $owner->id]);
        UserPinnedDungeonRouteCollection::create([
            'user_id'                     => $pinner->id,
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'order'                       => 0,
        ]);

        try {
            // Act
            $dungeonRouteCollection->delete();

            // Assert
            $this->assertSame(
                0,
                UserPinnedDungeonRouteCollection::where('dungeon_route_collection_id', $dungeonRouteCollection->id)->count(),
                'Deleting a collection must clean up any pins pointing at it',
            );
        } finally {
            $pinner->delete();
            $owner->delete();
        }
    }

    private function createTeam(): Team
    {
        return Team::create([
            'name'         => sprintf('Collection test %s', uniqid()),
            'public_key'   => Team::generateRandomPublicKey(),
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'description'  => 'Created by DungeonRouteCollectionTest',
            'icon_file_id' => -1,
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);
    }
}
