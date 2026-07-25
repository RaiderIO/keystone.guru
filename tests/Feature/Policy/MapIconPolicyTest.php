<?php

namespace Tests\Feature\Policy;

use App\Models\Laratrust\Role;
use App\Models\MapIcon;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use App\Policies\MapIconPolicy;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Policy')]
#[Group('MapIconPolicy')]
final class MapIconPolicyTest extends PublicTestCase
{
    private MapIconPolicy $policy;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new MapIconPolicy();
    }

    #[Test]
    public function createGlobal_givenAdmin_returnsAllowed(): void
    {
        // Act & Assert
        $this->assertTrue($this->policy->createGlobal($this->adminUser())->allowed());
    }

    #[Test]
    public function createGlobal_givenNonAdmin_returnsDenied(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            // Act & Assert
            $this->assertTrue($this->policy->createGlobal($user)->denied());
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function createGlobal_givenGuest_returnsDenied(): void
    {
        // Act & Assert
        $this->assertTrue($this->policy->createGlobal(null)->denied());
    }

    #[Test]
    public function update_givenRouteIcon_returnsAllowed(): void
    {
        // Arrange - a route icon is governed by the route's own edit gate, not by this one
        $user    = User::factory()->create();
        $mapIcon = new MapIcon();
        $mapIcon->setAttribute('dungeon_route_id', 1);
        $mapIcon->setAttribute('team_id', null);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->update($user, $mapIcon)->allowed());
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function update_givenTeamIcon_returnsAllowed(): void
    {
        // Arrange
        $user    = User::factory()->create();
        $mapIcon = new MapIcon();
        $mapIcon->setAttribute('dungeon_route_id', null);
        $mapIcon->setAttribute('team_id', 1);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->update($user, $mapIcon)->allowed());
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function update_givenMappingIconAndNonAdmin_returnsDenied(): void
    {
        // Arrange - no route and no team means the icon belongs to the mapping itself
        $user    = User::factory()->create();
        $mapIcon = new MapIcon();
        $mapIcon->setAttribute('dungeon_route_id', null);
        $mapIcon->setAttribute('team_id', null);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->update($user, $mapIcon)->denied());
            $this->assertTrue($this->policy->update($this->adminUser(), $mapIcon)->allowed());
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function delete_givenTeamIconAndNonAdmin_returnsDenied(): void
    {
        // Arrange - deliberately stricter than update(): pre-existing behaviour, see MapIconPolicy
        $user    = User::factory()->create();
        $mapIcon = new MapIcon();
        $mapIcon->setAttribute('dungeon_route_id', null);
        $mapIcon->setAttribute('team_id', 1);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->delete($user, $mapIcon)->denied());
            $this->assertTrue($this->policy->delete($this->adminUser(), $mapIcon)->allowed());
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function delete_givenRouteIcon_returnsAllowed(): void
    {
        // Arrange
        $user    = User::factory()->create();
        $mapIcon = new MapIcon();
        $mapIcon->setAttribute('dungeon_route_id', 1);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->delete($user, $mapIcon)->allowed());
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function assignToTeam_givenCollaborator_returnsAllowed(): void
    {
        // Arrange
        $user = User::factory()->create();
        $team = $this->createTeamWith($user, TeamUser::ROLE_COLLABORATOR);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->assignToTeam($user, new MapIcon(), $team));
        } finally {
            $this->deleteTeam($team);
            $user->delete();
        }
    }

    #[Test]
    public function assignToTeam_givenPlainMember_returnsDenied(): void
    {
        // Arrange - a plain member is not a collaborator
        $user = User::factory()->create();
        $team = $this->createTeamWith($user, TeamUser::ROLE_MEMBER);

        try {
            // Act & Assert
            $this->assertFalse($this->policy->assignToTeam($user, new MapIcon(), $team));
        } finally {
            $this->deleteTeam($team);
            $user->delete();
        }
    }

    #[Test]
    public function assignToTeam_givenNonMemberOrNoTeam_returnsDenied(): void
    {
        // Arrange
        $user     = User::factory()->create();
        $outsider = User::factory()->create();
        $team     = $this->createTeamWith($user, TeamUser::ROLE_ADMIN);

        try {
            // Act & Assert
            $this->assertFalse($this->policy->assignToTeam($outsider, new MapIcon(), $team));
            $this->assertFalse($this->policy->assignToTeam($user, new MapIcon(), null));
            $this->assertFalse($this->policy->assignToTeam(null, new MapIcon(), $team));
        } finally {
            $this->deleteTeam($team);
            $outsider->delete();
            $user->delete();
        }
    }

    private function createTeamWith(User $user, string $role): Team
    {
        $team = Team::create([
            'name'         => sprintf('Policy test %s', uniqid()),
            'public_key'   => Team::generateRandomPublicKey(),
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'description'  => 'Created by MapIconPolicyTest',
            'icon_file_id' => -1,
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);

        TeamUser::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role'    => $role,
        ]);

        return $team;
    }

    private function adminUser(): User
    {
        /** @var User $admin */
        $admin = User::findOrFail(1);
        $this->assertTrue(
            $admin->hasRole(Role::ROLE_ADMIN),
            'User id=1 must have the admin role for this test (seed the database).',
        );

        return $admin;
    }

    /**
     * Team's "deleting" hook walks members->patreonAdFreeGiveaway, which trips preventLazyLoading
     * unless the chain is eager-loaded first.
     */
    private function deleteTeam(Team $team): void
    {
        $team->load('members.patreonAdFreeGiveaway')->delete();
    }
}
