<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

#[Group('DungeonRouteSaveService')]
#[Group('DungeonRouteSaveServiceTeamAssignment')]
final class DungeonRouteSaveServiceTeamAssignmentTest extends DungeonRouteSaveServiceTestCase
{
    #[Test]
    public function save_givenTheAuthorMovingTheirOwnRouteToTheirOtherTeam_assignsIt(): void
    {
        // Arrange
        $author   = $this->createUser();
        $fromTeam = $this->createTeamWith($author, TeamUser::ROLE_ADMIN);
        $toTeam   = $this->createTeamWith($author, TeamUser::ROLE_MEMBER);
        $route    = $this->createTeamRoute($author, $fromTeam);

        try {
            // Act
            Auth::login($author);
            $result = $this->buildService(seasonService: $this->noSeasonService())
                ->save($route, ['team_id' => $toTeam->id]);

            // Assert
            $this->assertTrue($result);
            $this->assertSame($toTeam->id, DungeonRoute::query()->whereKey($route->id)->value('team_id'));
        } finally {
            $this->cleanUpAll($route, [$fromTeam, $toTeam], [$author]);
        }
    }

    #[Test]
    public function save_givenACollaboratorMovingSomeoneElsesRouteToTheirOwnTeam_leavesTheTeamUnchanged(): void
    {
        // Arrange - the collaborator may edit this route, but only because the team it sits on
        // granted them that. Re-homing it would take it away from exactly that team
        $author       = $this->createUser();
        $team         = $this->createTeamWith($author, TeamUser::ROLE_ADMIN);
        $collaborator = $this->createUser();
        $team->addMember($collaborator, TeamUser::ROLE_COLLABORATOR);
        $ownTeam = $this->createTeamWith($collaborator, TeamUser::ROLE_ADMIN);
        $route   = $this->createTeamRoute($author, $team);

        try {
            // Act
            Auth::login($collaborator);
            $result = $this->buildService(seasonService: $this->noSeasonService())
                ->save($route, ['team_id' => $ownTeam->id]);

            // Assert
            $this->assertTrue($result);
            $this->assertSame($team->id, DungeonRoute::query()->whereKey($route->id)->value('team_id'));
        } finally {
            $this->cleanUpAll($route, [$team, $ownTeam], [$author, $collaborator]);
        }
    }

    #[Test]
    public function save_givenACollaboratorDetachingSomeoneElsesRoute_leavesTheTeamUnchanged(): void
    {
        // Arrange - -1 is the "no team" sentinel the route edit form submits
        $author       = $this->createUser();
        $team         = $this->createTeamWith($author, TeamUser::ROLE_ADMIN);
        $collaborator = $this->createUser();
        $team->addMember($collaborator, TeamUser::ROLE_COLLABORATOR);
        $route = $this->createTeamRoute($author, $team);

        try {
            // Act
            Auth::login($collaborator);
            $result = $this->buildService(seasonService: $this->noSeasonService())
                ->save($route, ['team_id' => -1]);

            // Assert
            $this->assertTrue($result);
            $this->assertSame($team->id, DungeonRoute::query()->whereKey($route->id)->value('team_id'));
        } finally {
            $this->cleanUpAll($route, [$team], [$author, $collaborator]);
        }
    }

    #[Test]
    public function save_givenACollaboratorResubmittingTheTeamTheRouteAlreadyHas_savesNormally(): void
    {
        // Arrange - the ordinary case: the edit form round-trips the current team_id back
        $author       = $this->createUser();
        $team         = $this->createTeamWith($author, TeamUser::ROLE_ADMIN);
        $collaborator = $this->createUser();
        $team->addMember($collaborator, TeamUser::ROLE_COLLABORATOR);
        $route = $this->createTeamRoute($author, $team);

        try {
            // Act
            Auth::login($collaborator);
            $result = $this->buildService(seasonService: $this->noSeasonService())
                ->save($route, ['team_id' => $team->id, 'dungeon_route_title' => 'Edited by a collaborator']);

            // Assert
            $this->assertTrue($result);
            $this->assertSame($team->id, DungeonRoute::query()->whereKey($route->id)->value('team_id'));
            $this->assertSame('Edited by a collaborator', DungeonRoute::query()->whereKey($route->id)->value('title'));
        } finally {
            $this->cleanUpAll($route, [$team], [$author, $collaborator]);
        }
    }

    #[Test]
    public function save_givenTheAuthorWhoLeftTheTeamTheRouteSitsOn_keepsTheTeamAndSavesNormally(): void
    {
        // Arrange - leaving a team does not unassign the route, so its own author can no longer
        // pass an "author is a member of the target team" check for the team it is already on
        $author = $this->createUser();
        $team   = $this->createTeamWith($author, TeamUser::ROLE_ADMIN);
        $route  = $this->createTeamRoute($author, $team);
        TeamUser::query()->where('team_id', $team->id)->where('user_id', $author->id)->delete();
        $author->unsetRelation('teams');

        try {
            // Act
            Auth::login($author);
            $result = $this->buildService(seasonService: $this->noSeasonService())
                ->save($route, ['team_id' => $team->id, 'dungeon_route_title' => 'Edited after leaving']);

            // Assert
            $this->assertTrue($result);
            $this->assertSame($team->id, DungeonRoute::query()->whereKey($route->id)->value('team_id'));
            $this->assertSame('Edited after leaving', DungeonRoute::query()->whereKey($route->id)->value('title'));
        } finally {
            $this->cleanUpAll($route, [$team], [$author]);
        }
    }

    #[Test]
    public function save_givenTheAuthorAssigningATeamTheyAreNotAMemberOf_leavesTheTeamUnset(): void
    {
        // Arrange
        $author    = $this->createUser();
        $outsider  = $this->createUser();
        $otherTeam = $this->createTeamWith($outsider, TeamUser::ROLE_ADMIN);
        $route     = $this->createTeamRoute($author, null);

        try {
            // Act
            Auth::login($author);
            $result = $this->buildService(seasonService: $this->noSeasonService())
                ->save($route, ['team_id' => $otherTeam->id]);

            // Assert
            $this->assertTrue($result);
            $this->assertNull(DungeonRoute::query()->whereKey($route->id)->value('team_id'));
        } finally {
            $this->cleanUpAll($route, [$otherTeam], [$author, $outsider]);
        }
    }

    #[Test]
    public function save_givenAnAdminAssigningATeamTheAuthorIsNotAMemberOf_leavesTheTeamUnchanged(): void
    {
        // Arrange - a team only holds routes written by its own members, the same rule
        // AjaxTeamController::addRoute() applies. Being an admin does not lift it
        $author = $this->createUser();
        $admin  = $this->createUser();
        $admin->addRole(Role::ROLE_ADMIN);
        $adminTeam = $this->createTeamWith($admin, TeamUser::ROLE_ADMIN);
        $route     = $this->createTeamRoute($author, null);

        try {
            // Act
            Auth::login($admin);
            $result = $this->buildService(seasonService: $this->noSeasonService())
                ->save($route, ['team_id' => $adminTeam->id]);

            // Assert
            $this->assertTrue($result);
            $this->assertNull(DungeonRoute::query()->whereKey($route->id)->value('team_id'));
        } finally {
            $this->cleanUpAll($route, [$adminTeam], [$author, $admin]);
        }
    }

    #[Test]
    public function save_givenNoTeamIdInTheRequest_keepsTheTeamTheRouteAlreadyHas(): void
    {
        // Arrange - the ajax save path submits a partial payload without team_id at all
        $author = $this->createUser();
        $team   = $this->createTeamWith($author, TeamUser::ROLE_ADMIN);
        $route  = $this->createTeamRoute($author, $team);

        try {
            // Act
            Auth::login($author);
            $result = $this->buildService(seasonService: $this->noSeasonService())
                ->save($route, ['dungeon_route_title' => 'No team in the payload']);

            // Assert
            $this->assertTrue($result);
            $this->assertSame($team->id, DungeonRoute::query()->whereKey($route->id)->value('team_id'));
        } finally {
            $this->cleanUpAll($route, [$team], [$author]);
        }
    }

    #[Test]
    public function save_givenANewRouteAndATeamTheCreatorIsAMemberOf_assignsIt(): void
    {
        // Arrange - a new route has no author row yet, so the creator is the author
        $creator = $this->createUser();
        $team    = $this->createTeamWith($creator, TeamUser::ROLE_MEMBER);
        $dungeon = $this->getRetailDungeon();
        $route   = new DungeonRoute();

        try {
            // Act
            Auth::login($creator);
            $result = $this->buildService(
                seasonService: $this->noSeasonService(),
                thumbnailService: $this->thumbnailServiceAllowingRefresh(),
            )->save($route, [
                'dungeon_id'          => $dungeon->id,
                'faction_id'          => 1,
                'dungeon_route_title' => 'New route on a team',
                'team_id'             => $team->id,
            ]);

            // Assert
            $this->assertTrue($result);
            $this->assertSame($team->id, DungeonRoute::query()->whereKey($route->id)->value('team_id'));
        } finally {
            $this->cleanUpAll($route, [$team], [$creator]);
        }
    }

    /**
     * @return MockObject&SeasonServiceInterface
     */
    private function noSeasonService(): MockObject
    {
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn(null);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn(null);

        return $seasonService;
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }

    private function createTeamWith(User $user, string $role): Team
    {
        $team = Team::create([
            'public_key'               => Team::generateRandomPublicKey(),
            'name'                     => sprintf('Team assignment test %s', uniqid()),
            'description'              => 'Created by DungeonRouteSaveServiceTeamAssignmentTest',
            'invite_code'              => Team::generateRandomPublicKey(12, 'invite_code'),
            'icon_file_id'             => -1,
            'default_role'             => TeamUser::ROLE_MEMBER,
            'route_publishing_enabled' => true,
        ]);

        $team->addMember($user, $role);

        return $team;
    }

    private function createTeamRoute(User $author, ?Team $team): DungeonRoute
    {
        $dungeon = $this->getRetailDungeon();

        return DungeonRoute::factory()->create([
            'author_id'          => $author->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
            'team_id'            => $team?->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
    }

    /**
     * @param array<int, Team> $teams
     * @param array<int, User> $users
     */
    private function cleanUpAll(DungeonRoute $route, array $teams, array $users): void
    {
        Auth::logout();

        if ($route->exists) {
            $this->cleanupRoute($route);
        }

        foreach ($teams as $team) {
            TeamUser::query()->where('team_id', $team->id)->delete();
            Team::query()->whereKey($team->id)->delete();
        }

        foreach ($users as $user) {
            $user->delete();
        }
    }
}
