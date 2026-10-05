<?php

namespace Tests\Feature\Policy;

use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use App\Policies\TeamPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCases\PublicTestCase;

/**
 * changeRole() absorbed Team::canChangeRole(), which AjaxTeamController used to call inline after
 * the (coarser) moderator gate. These tests pin the rank ordering that logic enforces.
 */
#[Group('Policy')]
#[Group('TeamPolicy')]
final class TeamPolicyTest extends PublicTestCase
{
    private TeamPolicy $policy;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TeamPolicy();
    }

    #[Test]
    #[DataProvider('changeRoleProvider')]
    public function changeRole_givenRanks_returnsExpected(
        string $actorRole,
        string $targetRole,
        string $newRole,
        bool   $expected,
    ): void {
        // Arrange
        $actor  = User::factory()->create();
        $target = User::factory()->create();
        $team   = $this->createTeam();
        $this->addMember($team, $actor, $actorRole);
        $this->addMember($team, $target, $targetRole);

        try {
            // Act & Assert
            $this->assertSame($expected, $this->policy->changeRole($actor, $team->fresh(), $target, $newRole));
        } finally {
            $team->delete();
            $target->delete();
            $actor->delete();
        }
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: bool}>
     */
    public static function changeRoleProvider(): array
    {
        return [
            'admin promotes a member to moderator'          => [TeamUser::ROLE_ADMIN, TeamUser::ROLE_MEMBER, TeamUser::ROLE_MODERATOR, true],
            'moderator promotes a member to collaborator'   => [TeamUser::ROLE_MODERATOR, TeamUser::ROLE_MEMBER, TeamUser::ROLE_COLLABORATOR, true],
            'moderator may not promote past their own rank' => [TeamUser::ROLE_MODERATOR, TeamUser::ROLE_MEMBER, TeamUser::ROLE_ADMIN, false],
            'moderator may not change an admin'             => [TeamUser::ROLE_MODERATOR, TeamUser::ROLE_ADMIN, TeamUser::ROLE_MEMBER, false],
            'a plain member may not change anyone'          => [TeamUser::ROLE_MEMBER, TeamUser::ROLE_MEMBER, TeamUser::ROLE_MODERATOR, false],
            'a collaborator may not change anyone'          => [TeamUser::ROLE_COLLABORATOR, TeamUser::ROLE_MEMBER, TeamUser::ROLE_MODERATOR, false],
        ];
    }

    #[Test]
    public function changeRole_givenNonMemberActor_returnsDenied(): void
    {
        // Arrange
        $outsider = User::factory()->create();
        $target   = User::factory()->create();
        $team     = $this->createTeam();
        $this->addMember($team, $target, TeamUser::ROLE_MEMBER);

        try {
            // Act & Assert
            $this->assertFalse(
                $this->policy->changeRole($outsider, $team->fresh(), $target, TeamUser::ROLE_MODERATOR),
            );
        } finally {
            $team->delete();
            $target->delete();
            $outsider->delete();
        }
    }

    #[Test]
    public function moderateRoute_givenModerator_returnsAllowed(): void
    {
        // Arrange
        $user = User::factory()->create();
        $team = $this->createTeam();
        $this->addMember($team, $user, TeamUser::ROLE_MODERATOR);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->moderateRoute($user, $team->fresh()));
        } finally {
            $team->delete();
            $user->delete();
        }
    }

    #[Test]
    public function moderateRoute_givenPlainMember_returnsDenied(): void
    {
        // Arrange
        $user = User::factory()->create();
        $team = $this->createTeam();
        $this->addMember($team, $user, TeamUser::ROLE_MEMBER);

        try {
            // Act & Assert - AjaxTeamController used to repeat this check inline after the gate
            $this->assertFalse($this->policy->moderateRoute($user, $team->fresh()));
        } finally {
            $team->delete();
            $user->delete();
        }
    }

    #[Test]
    #[TestWith([TeamUser::ROLE_ADMIN, true])]
    #[TestWith([TeamUser::ROLE_MODERATOR, false])]
    #[TestWith([TeamUser::ROLE_MEMBER, false])]
    public function delete_givenTeamRole_returnsAllowedOnlyForTeamAdmins(string $role, bool $expected): void
    {
        // Arrange
        $user = User::factory()->create();
        $team = $this->createTeam();
        $this->addMember($team, $user, $role);

        try {
            // Act
            $result = $this->policy->delete($user, $team->fresh());

            // Assert
            $this->assertSame($expected, $result);
        } finally {
            $team->delete();
            $user->delete();
        }
    }

    #[Test]
    #[TestWith([TeamUser::ROLE_ADMIN, true])]
    #[TestWith([TeamUser::ROLE_MODERATOR, false])]
    public function changeDefaultRole_givenTeamRole_returnsAllowedOnlyForTeamAdmins(string $role, bool $expected): void
    {
        // Arrange
        $user = User::factory()->create();
        $team = $this->createTeam();
        $this->addMember($team, $user, $role);

        try {
            // Act
            $result = $this->policy->changeDefaultRole($user, $team->fresh());

            // Assert
            $this->assertSame($expected, $result);
        } finally {
            $team->delete();
            $user->delete();
        }
    }

    #[Test]
    #[TestWith([TeamUser::ROLE_MODERATOR, true])]
    #[TestWith([TeamUser::ROLE_COLLABORATOR, false])]
    #[TestWith([TeamUser::ROLE_MEMBER, false])]
    public function refreshInviteLink_givenTeamRole_returnsAllowedOnlyForModerators(string $role, bool $expected): void
    {
        // Arrange
        $user = User::factory()->create();
        $team = $this->createTeam();
        $this->addMember($team, $user, $role);

        try {
            // Act
            $result = $this->policy->refreshInviteLink($user, $team->fresh());

            // Assert
            $this->assertSame($expected, $result);
        } finally {
            $team->delete();
            $user->delete();
        }
    }

    #[Test]
    public function edit_givenMemberAndOutsider_returnsAllowedOnlyForTheMember(): void
    {
        // Arrange
        $member   = User::factory()->create();
        $outsider = User::factory()->create();
        $team     = $this->createTeam();
        $this->addMember($team, $member, TeamUser::ROLE_MEMBER);

        try {
            // Act
            $memberResult   = $this->policy->edit($member, $team->fresh());
            $outsiderResult = $this->policy->edit($outsider, $team->fresh());

            // Assert
            $this->assertTrue($memberResult);
            $this->assertFalse($outsiderResult);
        } finally {
            $team->delete();
            $outsider->delete();
            $member->delete();
        }
    }

    #[Test]
    #[DataProvider('removeMemberProvider')]
    public function removeMember_givenRanks_returnsExpected(string $actorRole, string $targetRole, bool $expected): void
    {
        // Arrange
        $actor  = User::factory()->create();
        $target = User::factory()->create();
        $team   = $this->createTeam();
        $this->addMember($team, $actor, $actorRole);
        $this->addMember($team, $target, $targetRole);

        try {
            // Act
            $result = $this->policy->removeMember($actor, $team->fresh(), $target);

            // Assert
            $this->assertSame($expected, $result);
        } finally {
            $team->delete();
            $target->delete();
            $actor->delete();
        }
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function removeMemberProvider(): array
    {
        return [
            'admin removes a moderator'            => [TeamUser::ROLE_ADMIN, TeamUser::ROLE_MODERATOR, true],
            'moderator removes a collaborator'     => [TeamUser::ROLE_MODERATOR, TeamUser::ROLE_COLLABORATOR, true],
            'moderator may not remove an admin'    => [TeamUser::ROLE_MODERATOR, TeamUser::ROLE_ADMIN, false],
            'moderator may not remove a moderator' => [TeamUser::ROLE_MODERATOR, TeamUser::ROLE_MODERATOR, false],
            'collaborator may not remove a member' => [TeamUser::ROLE_COLLABORATOR, TeamUser::ROLE_MEMBER, false],
        ];
    }

    #[Test]
    public function removeMember_givenAPlainMemberRemovingThemselves_returnsAllowed(): void
    {
        // Arrange
        $user = User::factory()->create();
        $team = $this->createTeam();
        $this->addMember($team, $user, TeamUser::ROLE_MEMBER);

        try {
            // Act
            $result = $this->policy->removeMember($user, $team->fresh(), $user);

            // Assert
            $this->assertTrue($result);
        } finally {
            $team->delete();
            $user->delete();
        }
    }

    #[Test]
    public function removeMember_givenAnOutsider_returnsDenied(): void
    {
        // Arrange - the outsider has no role at all, so no rank comparison can allow it
        $outsider = User::factory()->create();
        $target   = User::factory()->create();
        $team     = $this->createTeam();
        $this->addMember($team, $target, TeamUser::ROLE_MEMBER);

        try {
            // Act
            $result = $this->policy->removeMember($outsider, $team->fresh(), $target);

            // Assert
            $this->assertFalse($result);
        } finally {
            $team->delete();
            $target->delete();
            $outsider->delete();
        }
    }

    private function createTeam(): Team
    {
        return Team::create([
            'name'         => sprintf('Policy test %s', uniqid()),
            'public_key'   => Team::generateRandomPublicKey(),
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'description'  => 'Created by TeamPolicyTest',
            'icon_file_id' => -1,
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);
    }

    private function addMember(Team $team, User $user, string $role): void
    {
        TeamUser::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role'    => $role,
        ]);
    }
}
