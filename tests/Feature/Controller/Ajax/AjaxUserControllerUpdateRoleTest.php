<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Laratrust\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('User')]
final class AjaxUserControllerUpdateRoleTest extends AjaxPublicTestCase
{
    #[Test]
    public function updateRole_givenGrantOfRoleTheUserLacks_grantsIt(): void
    {
        $target = null;

        try {
            // Arrange
            $target = $this->createPlainUser();

            // Act
            $response = $this->postJson($this->url($target), ['role' => Role::ROLE_INTERNAL_TEAM, 'grant' => true]);

            // Assert
            $response->assertOk();
            $response->assertExactJson(['role' => Role::ROLE_INTERNAL_TEAM, 'has_role' => true]);
            $this->assertEqualsCanonicalizing(
                [Role::ROLE_USER, Role::ROLE_INTERNAL_TEAM],
                $this->roleNamesOf($target),
            );
        } finally {
            $target?->delete();
        }
    }

    #[Test]
    public function updateRole_givenRevokeOfRoleTheUserHas_removesIt(): void
    {
        $target = null;

        try {
            // Arrange
            $target = $this->createPlainUser();
            $target->addRole(Role::ROLE_INTERNAL_TEAM);

            // Act
            $response = $this->postJson($this->url($target), ['role' => Role::ROLE_INTERNAL_TEAM, 'grant' => false]);

            // Assert
            $response->assertOk();
            $response->assertExactJson(['role' => Role::ROLE_INTERNAL_TEAM, 'has_role' => false]);
            $this->assertEqualsCanonicalizing([Role::ROLE_USER], $this->roleNamesOf($target));
        } finally {
            $target?->delete();
        }
    }

    #[Test]
    public function updateRole_givenUserRole_resetsToOnlyTheUserRole(): void
    {
        $target = null;

        try {
            // Arrange - the user role is never toggled off, it strips every other role instead
            $target = $this->createPlainUser();
            $target->addRole(Role::ROLE_INTERNAL_TEAM);
            $target->addRole(Role::ROLE_BETA_TESTER);

            // Act
            $response = $this->postJson($this->url($target), ['role' => Role::ROLE_USER, 'grant' => true]);

            // Assert
            $response->assertOk();
            $response->assertExactJson(['role' => Role::ROLE_USER, 'has_role' => true]);
            $this->assertEqualsCanonicalizing([Role::ROLE_USER], $this->roleNamesOf($target));
        } finally {
            $target?->delete();
        }
    }

    #[Test]
    public function updateRole_givenAdminRoleBySuperAdmin_grantsIt(): void
    {
        $target = null;

        try {
            // Arrange
            config(['keystoneguru.super_admins' => [User::findOrFail(1)->name]]);
            $target = $this->createPlainUser();

            // Act
            $response = $this->postJson($this->url($target), ['role' => Role::ROLE_ADMIN, 'grant' => true]);

            // Assert
            $response->assertOk();
            $response->assertExactJson(['role' => Role::ROLE_ADMIN, 'has_role' => true]);
            $this->assertEqualsCanonicalizing([Role::ROLE_USER, Role::ROLE_ADMIN], $this->roleNamesOf($target));
        } finally {
            $target?->delete();
        }
    }

    #[Test]
    public function updateRole_givenAdminRoleByAdminWhoIsNotASuperAdmin_returnsForbidden(): void
    {
        $target = null;

        try {
            // Arrange
            config(['keystoneguru.super_admins' => ['SomebodyElse']]);
            $target = $this->createPlainUser();

            // Act
            $response = $this->postJson($this->url($target), ['role' => Role::ROLE_ADMIN, 'grant' => true]);

            // Assert
            $response->assertForbidden();
            $this->assertEqualsCanonicalizing([Role::ROLE_USER], $this->roleNamesOf($target));
        } finally {
            $target?->delete();
        }
    }

    #[Test]
    public function updateRole_givenUserRoleOnAnAdminByAdminWhoIsNotASuperAdmin_returnsForbidden(): void
    {
        $target = null;

        try {
            // Arrange - resetting an admin to a plain user would strip their admin role
            config(['keystoneguru.super_admins' => ['SomebodyElse']]);
            $target = $this->createPlainUser();
            $target->addRole(Role::ROLE_ADMIN);

            // Act
            $response = $this->postJson($this->url($target), ['role' => Role::ROLE_USER, 'grant' => true]);

            // Assert
            $response->assertForbidden();
            $this->assertEqualsCanonicalizing([Role::ROLE_USER, Role::ROLE_ADMIN], $this->roleNamesOf($target));
        } finally {
            $target?->delete();
        }
    }

    #[Test]
    public function updateRole_givenNonAdmin_returnsForbidden(): void
    {
        $actor  = null;
        $target = null;

        try {
            // Arrange
            $actor  = $this->createPlainUser();
            $target = $this->createPlainUser();
            $this->actingAs($actor);

            // Act
            $response = $this->postJson($this->url($target), ['role' => Role::ROLE_INTERNAL_TEAM, 'grant' => true]);

            // Assert
            $response->assertForbidden();
            $this->assertEqualsCanonicalizing([Role::ROLE_USER], $this->roleNamesOf($target));
        } finally {
            $target?->delete();
            $actor?->delete();
        }
    }

    #[Test]
    public function updateRole_givenUnknownRole_returnsValidationError(): void
    {
        $target = null;

        try {
            // Arrange
            $target = $this->createPlainUser();

            // Act
            $response = $this->postJson($this->url($target), ['role' => 'not_a_role', 'grant' => true]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['role' => __('validation.custom.role.exists')]);
            $this->assertEqualsCanonicalizing([Role::ROLE_USER], $this->roleNamesOf($target));
        } finally {
            $target?->delete();
        }
    }

    #[Test]
    public function updateRole_givenStaleRevokeOfRoleTheUserNoLongerHas_leavesItRevoked(): void
    {
        $target = null;

        try {
            // Arrange - a stale "Remove" request for a role the user does not have
            $target = $this->createPlainUser();

            // Act
            $response = $this->postJson($this->url($target), ['role' => Role::ROLE_INTERNAL_TEAM, 'grant' => false]);

            // Assert
            $response->assertOk();
            $response->assertExactJson(['role' => Role::ROLE_INTERNAL_TEAM, 'has_role' => false]);
            $this->assertEqualsCanonicalizing([Role::ROLE_USER], $this->roleNamesOf($target));
        } finally {
            $target?->delete();
        }
    }

    #[Test]
    public function updateRole_givenRepeatedGrantOfRoleTheUserHas_keepsItGranted(): void
    {
        $target = null;

        try {
            // Arrange - a repeated "Make" click arrives after the first one already granted the role
            $target = $this->createPlainUser();
            $target->addRole(Role::ROLE_INTERNAL_TEAM);

            // Act
            $response = $this->postJson($this->url($target), ['role' => Role::ROLE_INTERNAL_TEAM, 'grant' => true]);

            // Assert
            $response->assertOk();
            $response->assertExactJson(['role' => Role::ROLE_INTERNAL_TEAM, 'has_role' => true]);
            $this->assertEqualsCanonicalizing([Role::ROLE_USER, Role::ROLE_INTERNAL_TEAM], $this->roleNamesOf($target));
        } finally {
            $target?->delete();
        }
    }

    #[Test]
    public function updateRole_givenNoGrant_returnsValidationError(): void
    {
        $target = null;

        try {
            // Arrange
            $target = $this->createPlainUser();

            // Act
            $response = $this->postJson($this->url($target), ['role' => Role::ROLE_INTERNAL_TEAM]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['grant']);
            $response->assertJsonMissingValidationErrors(['role']);
            $this->assertEqualsCanonicalizing([Role::ROLE_USER], $this->roleNamesOf($target));
        } finally {
            $target?->delete();
        }
    }

    private function createPlainUser(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }

    private function url(User $target): string
    {
        return sprintf('/ajax/admin/user/%d/role', $target->id);
    }

    /**
     * @return array<int, string>
     */
    private function roleNamesOf(User $user): array
    {
        return $user->roles()->pluck('name')->all();
    }
}
