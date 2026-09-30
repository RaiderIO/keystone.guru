<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteScheduledPublish;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\DungeonRouteTestBase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteScheduledPublishControllerTest extends DungeonRouteTestBase
{
    private Team $team;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::create([
            'public_key'               => Team::generateRandomPublicKey(),
            'name'                     => 'Test Team',
            'description'              => '',
            'invite_code'              => Team::generateRandomPublicKey(12, 'invite_code'),
            'default_role'             => TeamUser::ROLE_MEMBER,
            'route_publishing_enabled' => true,
        ]);

        $this->dungeonRoute->team_id            = $this->team->id;
        $this->dungeonRoute->published_state_id = PublishedState::ALL[PublishedState::TEAM];
        $this->dungeonRoute->save();

        // User 1 is logged in (from AjaxPublicTestCase); make them a moderator
        TeamUser::create([
            'team_id' => $this->team->id,
            'user_id' => 1,
            'role'    => TeamUser::ROLE_MODERATOR,
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            DungeonRouteScheduledPublish::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
            TeamUser::where('team_id', $this->team->id)->delete();
            $this->team->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function storeScheduledPublish_givenValidData_returnsNoContent(): void
    {
        // Arrange
        $publishAt = Carbon::now()->addDay()->format('Y-m-d H:i:s');

        // Act
        $response = $this->put(
            route('api.dungeonroute.scheduledpublish.store', ['dungeonRoute' => $this->dungeonRoute]),
            ['published_state' => PublishedState::WORLD, 'publish_at' => $publishAt],
        );

        // Assert
        $response->assertNoContent();
        $this->assertDatabaseHas('dungeon_route_scheduled_publishes', [
            'dungeon_route_id' => $this->dungeonRoute->id,
            'published_state'  => PublishedState::WORLD,
        ]);
    }

    #[Test]
    public function storeScheduledPublish_givenPastDate_returnsUnprocessableEntity(): void
    {
        // Arrange
        $publishAt = Carbon::now()->subDay()->format('Y-m-d H:i:s');

        // Act
        $response = $this->withHeaders(['Accept' => 'application/json'])->put(
            route('api.dungeonroute.scheduledpublish.store', ['dungeonRoute' => $this->dungeonRoute]),
            ['published_state' => PublishedState::WORLD, 'publish_at' => $publishAt],
        );

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['publish_at']);
        $this->assertDatabaseMissing('dungeon_route_scheduled_publishes', ['dungeon_route_id' => $this->dungeonRoute->id]);
    }

    #[Test]
    public function storeScheduledPublish_givenInvalidPublishedState_returnsUnprocessableEntity(): void
    {
        // Arrange
        $publishAt = Carbon::now()->addDay()->format('Y-m-d H:i:s');

        // Act
        $response = $this->withHeaders(['Accept' => 'application/json'])->put(
            route('api.dungeonroute.scheduledpublish.store', ['dungeonRoute' => $this->dungeonRoute]),
            ['published_state' => PublishedState::UNPUBLISHED, 'publish_at' => $publishAt],
        );

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['published_state']);
        $this->assertDatabaseMissing('dungeon_route_scheduled_publishes', ['dungeon_route_id' => $this->dungeonRoute->id]);
    }

    #[Test]
    public function storeScheduledPublish_givenTeamModeratorWhoIsNotAdmin_storesTheSchedule(): void
    {
        // Arrange
        $moderator = $this->createTeamMember(TeamUser::ROLE_MODERATOR);

        try {
            $this->actingAs($moderator);

            // Act
            $response = $this->put(
                route('api.dungeonroute.scheduledpublish.store', ['dungeonRoute' => $this->dungeonRoute]),
                ['published_state' => PublishedState::WORLD, 'publish_at' => Carbon::now()->addDay()->format('Y-m-d H:i:s')],
            );

            // Assert
            $response->assertNoContent();
            $this->assertDatabaseHas('dungeon_route_scheduled_publishes', [
                'dungeon_route_id' => $this->dungeonRoute->id,
                'published_state'  => PublishedState::WORLD,
            ]);
        } finally {
            $moderator->delete();
        }
    }

    #[Test]
    public function storeScheduledPublish_givenTeamMemberWhoIsNotModerator_returnsForbidden(): void
    {
        // Arrange
        $member = $this->createTeamMember(TeamUser::ROLE_MEMBER);

        try {
            $this->actingAs($member);

            // Act
            $response = $this->put(
                route('api.dungeonroute.scheduledpublish.store', ['dungeonRoute' => $this->dungeonRoute]),
                ['published_state' => PublishedState::WORLD, 'publish_at' => Carbon::now()->addDay()->format('Y-m-d H:i:s')],
            );

            // Assert
            $response->assertForbidden();
            $this->assertDatabaseMissing('dungeon_route_scheduled_publishes', ['dungeon_route_id' => $this->dungeonRoute->id]);
        } finally {
            $member->delete();
        }
    }

    #[Test]
    public function storeScheduledPublish_givenWorldWithLinkWithoutPatreonBenefit_returnsUnprocessableEntity(): void
    {
        // Arrange
        $moderator = $this->createTeamMember(TeamUser::ROLE_MODERATOR);

        try {
            $this->actingAs($moderator);

            // Act
            $response = $this->withHeaders(['Accept' => 'application/json'])->put(
                route('api.dungeonroute.scheduledpublish.store', ['dungeonRoute' => $this->dungeonRoute]),
                ['published_state' => PublishedState::WORLD_WITH_LINK, 'publish_at' => Carbon::now()->addDay()->format('Y-m-d H:i:s')],
            );

            // Assert
            $response->assertUnprocessable();
            $this->assertDatabaseMissing('dungeon_route_scheduled_publishes', ['dungeon_route_id' => $this->dungeonRoute->id]);
        } finally {
            $moderator->delete();
        }
    }

    #[Test]
    public function storeScheduledPublish_givenNonTeamMemberUser_returnsForbidden(): void
    {
        // Arrange — create a route not belonging to any team
        /** @var DungeonRoute $routeWithoutTeam */
        $routeWithoutTeam = DungeonRoute::factory()->create();

        try {
            $publishAt = Carbon::now()->addDay()->format('Y-m-d H:i:s');

            // Act
            $response = $this->put(
                route('api.dungeonroute.scheduledpublish.store', ['dungeonRoute' => $routeWithoutTeam]),
                ['published_state' => PublishedState::WORLD, 'publish_at' => $publishAt],
            );

            // Assert
            $response->assertForbidden();
        } finally {
            $routeWithoutTeam->delete();
        }
    }

    #[Test]
    public function storeScheduledPublish_givenExistingSchedule_updatesExistingRecord(): void
    {
        // Arrange
        $existing = DungeonRouteScheduledPublish::create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'published_state'  => PublishedState::TEAM,
            'publish_at'       => Carbon::now()->addDay(),
        ]);

        try {
            $newPublishAt = Carbon::now()->addDays(2)->format('Y-m-d H:i:s');

            // Act
            $response = $this->put(
                route('api.dungeonroute.scheduledpublish.store', ['dungeonRoute' => $this->dungeonRoute]),
                ['published_state' => PublishedState::WORLD, 'publish_at' => $newPublishAt],
            );

            // Assert
            $response->assertNoContent();
            $this->assertEquals(
                1,
                DungeonRouteScheduledPublish::where('dungeon_route_id', $this->dungeonRoute->id)->count(),
            );
            $this->assertSame(PublishedState::WORLD, $existing->fresh()->published_state);
            $this->assertTrue($existing->fresh()->publish_at->greaterThan(Carbon::now()->addDay()->addHour()));
        } finally {
            DungeonRouteScheduledPublish::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        }
    }

    #[Test]
    public function destroyScheduledPublish_givenExistingSchedule_returnsNoContent(): void
    {
        // Arrange
        DungeonRouteScheduledPublish::create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'published_state'  => PublishedState::TEAM,
            'publish_at'       => Carbon::now()->addDay(),
        ]);

        // Act
        $response = $this->delete(
            route('api.dungeonroute.scheduledpublish.destroy', ['dungeonRoute' => $this->dungeonRoute]),
        );

        // Assert
        $response->assertNoContent();
        $this->assertDatabaseMissing('dungeon_route_scheduled_publishes', [
            'dungeon_route_id' => $this->dungeonRoute->id,
        ]);
    }

    #[Test]
    public function destroyScheduledPublish_givenNoExistingSchedule_returnsNoContent(): void
    {
        // Arrange — no scheduled publish exists

        // Act
        $response = $this->delete(
            route('api.dungeonroute.scheduledpublish.destroy', ['dungeonRoute' => $this->dungeonRoute]),
        );

        // Assert — idempotent delete
        $response->assertNoContent();
    }

    #[Test]
    public function destroyScheduledPublish_givenTeamMemberWhoIsNotModerator_returnsForbidden(): void
    {
        // Arrange
        $member = $this->createTeamMember(TeamUser::ROLE_MEMBER);
        DungeonRouteScheduledPublish::create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'published_state'  => PublishedState::WORLD,
            'publish_at'       => Carbon::now()->addDay(),
        ]);

        try {
            $this->actingAs($member);

            // Act
            $response = $this->delete(
                route('api.dungeonroute.scheduledpublish.destroy', ['dungeonRoute' => $this->dungeonRoute]),
            );

            // Assert
            $response->assertForbidden();
            $this->assertDatabaseHas('dungeon_route_scheduled_publishes', ['dungeon_route_id' => $this->dungeonRoute->id]);
        } finally {
            $member->delete();
        }
    }

    /**
     * A non-admin user with the user role, so neither the role middleware nor the policy's admin
     * branch decides the outcome - only the team role does.
     */
    private function createTeamMember(string $teamRole): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        TeamUser::create([
            'team_id' => $this->team->id,
            'user_id' => $user->id,
            'role'    => $teamRole,
        ]);

        return $user;
    }
}
