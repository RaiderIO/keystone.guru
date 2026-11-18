<?php

namespace Tests\Feature\Policy;

use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonAdFreeGiveaway;
use App\Models\User;
use App\Policies\UserPolicy;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Policy')]
#[Group('UserPolicy')]
final class UserPolicyTest extends PublicTestCase
{
    private UserPolicy $policy;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new UserPolicy();
    }

    #[Test]
    public function update_givenSelf_returnsAllowed(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            // Act & Assert
            $this->assertTrue($this->policy->update($user, $user));
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function update_givenAnotherUser_returnsDenied(): void
    {
        // Arrange
        $user  = User::factory()->create();
        $other = User::factory()->create();

        try {
            // Act & Assert - not even an admin may edit someone else's settings
            $this->assertFalse($this->policy->update($user, $other));
            $this->assertFalse($this->policy->update($this->adminUser(), $other));
        } finally {
            $other->delete();
            $user->delete();
        }
    }

    #[Test]
    public function delete_givenSelf_returnsAllowed(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            // Act & Assert
            $this->assertTrue($this->policy->delete($user, $user)->allowed());
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function delete_givenAnotherUser_returnsDenied(): void
    {
        // Arrange
        $user  = User::factory()->create();
        $other = User::factory()->create();

        try {
            // Act & Assert
            $this->assertTrue($this->policy->delete($user, $other)->denied());
        } finally {
            $other->delete();
            $user->delete();
        }
    }

    #[Test]
    public function delete_givenAdminDeletingThemselves_returnsDenied(): void
    {
        // Arrange
        $admin = $this->adminUser();

        // Act
        $result = $this->policy->delete($admin, $admin);

        // Assert
        $this->assertTrue($result->denied());
        $this->assertSame(__('controller.profile.flash.admins_cannot_delete_themselves'), $result->message());
    }

    #[Test]
    public function makeRole_givenNonAdmin_returnsDenied(): void
    {
        // Arrange
        $user   = User::factory()->create();
        $target = User::factory()->create();

        try {
            // Act & Assert
            $this->assertTrue($this->policy->makeRole($user, $target, Role::ROLE_USER)->denied());
        } finally {
            $target->delete();
            $user->delete();
        }
    }

    #[Test]
    public function makeRole_givenAdminGrantingNonAdminRole_returnsAllowed(): void
    {
        // Arrange
        $target = User::factory()->create();

        try {
            // Act & Assert
            $this->assertTrue($this->policy->makeRole($this->adminUser(), $target, Role::ROLE_USER)->allowed());
        } finally {
            $target->delete();
        }
    }

    #[Test]
    public function makeRole_givenAdminWhoIsNotASuperAdminGrantingAdmin_returnsDenied(): void
    {
        // Arrange - an admin whose id is not listed in keystoneguru.super_admin_user_ids
        $admin      = $this->createAdmin();
        $superAdmin = $this->createAdmin();
        $target     = User::factory()->create();
        config(['keystoneguru.super_admin_user_ids' => [$superAdmin->id]]);

        try {
            // Act
            $result = $this->policy->makeRole($admin, $target, Role::ROLE_ADMIN);

            // Assert
            $this->assertTrue($result->denied());
            $this->assertSame(__('policy.make_role_only_super_admins_may_grant_admin'), $result->message());
        } finally {
            $target->delete();
            $superAdmin->delete();
            $admin->delete();
        }
    }

    #[Test]
    public function makeRole_givenAdminNamedAdminWhoIsNotASuperAdminGrantingAdmin_returnsDenied(): void
    {
        // Arrange - a display name can be changed or re-used, so it must not make anyone a super admin
        $admin      = $this->createAdmin(['name' => 'Admin']);
        $superAdmin = $this->createAdmin();
        $target     = User::factory()->create();
        config(['keystoneguru.super_admin_user_ids' => [$superAdmin->id]]);

        try {
            // Act
            $result = $this->policy->makeRole($admin, $target, Role::ROLE_ADMIN);

            // Assert
            $this->assertTrue($result->denied());
            $this->assertSame(__('policy.make_role_only_super_admins_may_grant_admin'), $result->message());
        } finally {
            $target->delete();
            $superAdmin->delete();
            $admin->delete();
        }
    }

    #[Test]
    public function makeRole_givenAdminWhoIsNotASuperAdminResettingAnAdminToUser_returnsDenied(): void
    {
        // Arrange - the user role resets the target to only that role, which strips admin
        $admin      = $this->createAdmin();
        $superAdmin = $this->createAdmin();
        $target     = $this->createAdmin();
        config(['keystoneguru.super_admin_user_ids' => [$superAdmin->id]]);

        try {
            // Act
            $result = $this->policy->makeRole($admin, $target, Role::ROLE_USER);

            // Assert
            $this->assertTrue($result->denied());
            $this->assertSame(__('policy.make_role_only_super_admins_may_grant_admin'), $result->message());
        } finally {
            $target->delete();
            $superAdmin->delete();
            $admin->delete();
        }
    }

    #[Test]
    public function makeRole_givenSuperAdminGrantingAdmin_returnsAllowed(): void
    {
        // Arrange
        $superAdmin = $this->createAdmin();
        $target     = User::factory()->create();
        config(['keystoneguru.super_admin_user_ids' => [$superAdmin->id]]);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->makeRole($superAdmin, $target, Role::ROLE_ADMIN)->allowed());
        } finally {
            $target->delete();
            $superAdmin->delete();
        }
    }

    #[Test]
    public function adminDelete_givenNonAdmin_returnsDenied(): void
    {
        // Arrange
        $user   = User::factory()->create();
        $target = User::factory()->create();
        config(['keystoneguru.super_admin_user_ids' => []]);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->adminDelete($user, $target)->denied());
        } finally {
            $target->delete();
            $user->delete();
        }
    }

    #[Test]
    public function adminDelete_givenAdminDeletingPlainUser_returnsAllowed(): void
    {
        // Arrange
        $admin  = $this->createAdmin();
        $target = User::factory()->create();
        config(['keystoneguru.super_admin_user_ids' => []]);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->adminDelete($admin, $target)->allowed());
        } finally {
            $target->delete();
            $admin->delete();
        }
    }

    #[Test]
    public function adminDelete_givenAdminWhoIsNotASuperAdminDeletingAnAdmin_returnsDenied(): void
    {
        // Arrange
        $admin      = $this->createAdmin();
        $superAdmin = $this->createAdmin();
        $target     = $this->createAdmin();
        config(['keystoneguru.super_admin_user_ids' => [$superAdmin->id]]);

        try {
            // Act
            $result = $this->policy->adminDelete($admin, $target);

            // Assert
            $this->assertTrue($result->denied());
            $this->assertSame(__('policy.admin_delete_only_super_admins_may_delete_admins'), $result->message());
        } finally {
            $target->delete();
            $superAdmin->delete();
            $admin->delete();
        }
    }

    #[Test]
    public function adminDelete_givenSuperAdminDeletingAnAdmin_returnsAllowed(): void
    {
        // Arrange
        $superAdmin = $this->createAdmin();
        $target     = $this->createAdmin();
        config(['keystoneguru.super_admin_user_ids' => [$superAdmin->id]]);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->adminDelete($superAdmin, $target)->allowed());
        } finally {
            $target->delete();
            $superAdmin->delete();
        }
    }

    #[Test]
    public function adminDelete_givenSuperAdminAsTarget_returnsDenied(): void
    {
        // Arrange - not even another super admin may delete one
        $superAdmin = $this->createAdmin();
        $target     = $this->createAdmin();
        config(['keystoneguru.super_admin_user_ids' => [$superAdmin->id, $target->id]]);

        try {
            // Act
            $result = $this->policy->adminDelete($superAdmin, $target);

            // Assert
            $this->assertTrue($result->denied());
            $this->assertSame(__('policy.admin_delete_super_admin_not_allowed'), $result->message());
        } finally {
            $target->delete();
            $superAdmin->delete();
        }
    }

    #[Test]
    public function revokeAdFreeGiveaway_givenTheGiver_returnsAllowed(): void
    {
        // Arrange
        $giver    = User::factory()->create();
        $receiver = User::factory()->create();
        $giveaway = PatreonAdFreeGiveaway::create([
            'giver_user_id'    => $giver->id,
            'receiver_user_id' => $receiver->id,
        ]);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->revokeAdFreeGiveaway($giver, $receiver->fresh())->allowed());
        } finally {
            $giveaway->delete();
            $receiver->delete();
            $giver->delete();
        }
    }

    #[Test]
    public function revokeAdFreeGiveaway_givenSomebodyElse_returnsDenied(): void
    {
        // Arrange
        $giver     = User::factory()->create();
        $receiver  = User::factory()->create();
        $bystander = User::factory()->create();
        $giveaway  = PatreonAdFreeGiveaway::create([
            'giver_user_id'    => $giver->id,
            'receiver_user_id' => $receiver->id,
        ]);

        try {
            // Act & Assert - not even an admin may revoke somebody else's giveaway
            $this->assertTrue($this->policy->revokeAdFreeGiveaway($bystander, $receiver->fresh())->denied());
            $this->assertTrue($this->policy->revokeAdFreeGiveaway($this->adminUser(), $receiver->fresh())->denied());
        } finally {
            $giveaway->delete();
            $bystander->delete();
            $receiver->delete();
            $giver->delete();
        }
    }

    #[Test]
    public function can_givenUpdateAbilityAndSelf_resolvesThroughGate(): void
    {
        // Arrange - proves UserPolicy is wired to the Gate via auto-discovery
        $user = User::factory()->create();

        try {
            // Act & Assert
            $this->assertTrue($user->can('update', $user));
        } finally {
            $user->delete();
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createAdmin(array $attributes = []): User
    {
        $admin = User::factory()->create($attributes);
        $admin->addRole(Role::ROLE_ADMIN);

        return $admin;
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
}
