<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

/**
 * DELETE /ajax/team/{team}/member/{user}: leaving a team, and removing someone else from it.
 */
#[Group('Controller')]
#[Group('Team')]
final class AjaxTeamControllerRemoveMemberTest extends AjaxPublicTestCase
{
    private Team $team;

    private User $teamAdmin;

    private User $moderator;

    private User $member;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::create([
            'name'         => sprintf('Ajax team remove member test %s', uniqid()),
            'public_key'   => Team::generateRandomPublicKey(),
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'description'  => 'Created by AjaxTeamControllerRemoveMemberTest',
            'icon_file_id' => -1,
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);

        $this->teamAdmin = $this->createTeamMember(TeamUser::ROLE_ADMIN);
        $this->moderator = $this->createTeamMember(TeamUser::ROLE_MODERATOR);
        $this->member    = $this->createTeamMember(TeamUser::ROLE_MEMBER);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            // The team may already be gone when a test disbanded it
            Team::query()->whereKey($this->team->id)->first()?->delete();
            $this->member->delete();
            $this->moderator->delete();
            $this->teamAdmin->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function removeMember_givenMemberRemovingThemselves_returnsNoContentAndRemovesMembership(): void
    {
        // Arrange
        $this->actingAs($this->member);

        // Act
        $response = $this->delete($this->memberUrl($this->member));

        // Assert
        $response->assertNoContent();
        $this->assertFalse($this->isTeamMember($this->member));
        $this->assertTrue($this->isTeamMember($this->teamAdmin));
    }

    #[Test]
    public function removeMember_givenModeratorRemovingPlainMember_returnsNoContentAndRemovesMembership(): void
    {
        // Arrange
        $this->actingAs($this->moderator);

        // Act
        $response = $this->delete($this->memberUrl($this->member));

        // Assert
        $response->assertNoContent();
        $this->assertFalse($this->isTeamMember($this->member));
    }

    #[Test]
    public function removeMember_givenTeamAdminRemovingModerator_returnsNoContentAndRemovesMembership(): void
    {
        // Arrange
        $this->actingAs($this->teamAdmin);

        // Act
        $response = $this->delete($this->memberUrl($this->moderator));

        // Assert
        $response->assertNoContent();
        $this->assertFalse($this->isTeamMember($this->moderator));
        $this->assertSame(TeamUser::ROLE_ADMIN, $this->roleOf($this->teamAdmin));
    }

    #[Test]
    public function removeMember_givenModeratorRemovingAnotherModerator_returnsForbiddenAndKeepsMembership(): void
    {
        // Arrange - a moderator only outranks plain members and collaborators
        $otherModerator = $this->createTeamMember(TeamUser::ROLE_MODERATOR);
        $this->actingAs($this->moderator);

        try {
            // Act
            $response = $this->delete($this->memberUrl($otherModerator));

            // Assert
            $response->assertForbidden();
            $this->assertTrue($this->isTeamMember($otherModerator));
        } finally {
            TeamUser::query()->where('team_id', $this->team->id)->where('user_id', $otherModerator->id)->delete();
            $otherModerator->delete();
        }
    }

    #[Test]
    public function removeMember_givenModeratorRemovingTeamAdmin_returnsForbiddenAndKeepsMembership(): void
    {
        // Arrange
        $this->actingAs($this->moderator);

        // Act
        $response = $this->delete($this->memberUrl($this->teamAdmin));

        // Assert
        $response->assertForbidden();
        $this->assertSame(TeamUser::ROLE_ADMIN, $this->roleOf($this->teamAdmin));
    }

    #[Test]
    public function removeMember_givenPlainMemberRemovingSomeoneElse_returnsForbiddenAndKeepsMembership(): void
    {
        // Arrange
        $this->actingAs($this->member);

        // Act
        $response = $this->delete($this->memberUrl($this->moderator));

        // Assert
        $response->assertForbidden();
        $this->assertTrue($this->isTeamMember($this->moderator));
    }

    #[Test]
    public function removeMember_givenUserOutsideTheTeam_returnsForbiddenAndKeepsMembership(): void
    {
        // Arrange - the seeded site admin is not part of this team; being a site admin grants nothing here
        // AjaxPublicTestCase already acts as the site admin

        // Act
        $response = $this->delete($this->memberUrl($this->member));

        // Assert
        $response->assertForbidden();
        $this->assertTrue($this->isTeamMember($this->member));
    }

    #[Test]
    public function removeMember_givenTargetWhoIsNotInTheTeam_returnsForbidden(): void
    {
        // Arrange
        $outsider = User::factory()->create();
        $outsider->addRole(Role::ROLE_USER);
        $this->actingAs($this->teamAdmin);

        try {
            // Act
            $response = $this->delete($this->memberUrl($outsider));

            // Assert
            $response->assertForbidden();
        } finally {
            $outsider->delete();
        }
    }

    #[Test]
    public function removeMember_givenGuest_returnsUnauthorizedAndKeepsMembership(): void
    {
        // Arrange
        auth()->logout();

        // Act
        $response = $this->deleteJson($this->memberUrl($this->member));

        // Assert
        $response->assertUnauthorized();
        $this->assertTrue($this->isTeamMember($this->member));
    }

    #[Test]
    public function removeMember_givenMemberWithTeamRoutes_unassignsOnlyTheirRoutesFromTheTeam(): void
    {
        // Arrange
        $leavingMembersRoute = DungeonRoute::factory()->create([
            'author_id'  => $this->member->id,
            'team_id'    => $this->team->id,
            'expires_at' => null,
        ]);
        $stayingMembersRoute = DungeonRoute::factory()->create([
            'author_id'  => $this->moderator->id,
            'team_id'    => $this->team->id,
            'expires_at' => null,
        ]);
        $this->actingAs($this->member);

        try {
            // Act
            $response = $this->delete($this->memberUrl($this->member));

            // Assert
            $response->assertNoContent();
            $this->assertNull(DungeonRoute::query()->whereKey($leavingMembersRoute->id)->value('team_id'));
            $this->assertSame($this->team->id, DungeonRoute::query()->whereKey($stayingMembersRoute->id)->value('team_id'));
        } finally {
            $leavingMembersRoute->delete();
            $stayingMembersRoute->delete();
        }
    }

    #[Test]
    public function removeMember_givenTheLastMemberLeaving_disbandsTheTeam(): void
    {
        // Arrange - leave only the admin in the team
        TeamUser::query()->where('team_id', $this->team->id)->whereIn('user_id', [$this->moderator->id, $this->member->id])->delete();
        $this->actingAs($this->teamAdmin);

        // Act
        $response = $this->delete($this->memberUrl($this->teamAdmin));

        // Assert
        $response->assertNoContent();
        $this->assertFalse(Team::query()->whereKey($this->team->id)->exists());
        $this->assertFalse(TeamUser::query()->where('team_id', $this->team->id)->exists());
    }

    #[Test]
    public function removeMember_givenMembersRemainAfterRemoval_keepsTheTeam(): void
    {
        // Arrange
        $this->actingAs($this->teamAdmin);

        // Act
        $response = $this->delete($this->memberUrl($this->member));

        // Assert
        $response->assertNoContent();
        $this->assertTrue(Team::query()->whereKey($this->team->id)->exists());
    }

    private function createTeamMember(string $role): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        TeamUser::create(['team_id' => $this->team->id, 'user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    private function isTeamMember(User $user): bool
    {
        return TeamUser::query()->where('team_id', $this->team->id)->where('user_id', $user->id)->exists();
    }

    private function roleOf(User $user): ?string
    {
        return TeamUser::query()->where('team_id', $this->team->id)->where('user_id', $user->id)->value('role');
    }

    private function memberUrl(User $user): string
    {
        return sprintf('/ajax/team/%s/member/%s', $this->team->getRouteKey(), $user->getRouteKey());
    }
}
