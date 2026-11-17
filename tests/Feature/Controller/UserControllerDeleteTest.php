<?php

namespace Tests\Feature\Controller;

use App\Models\Laratrust\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
#[Group('User')]
final class UserControllerDeleteTest extends PublicTestCase
{
    #[Test]
    public function delete_givenAdminDeletingPlainUser_deletesTheUser(): void
    {
        $admin  = null;
        $target = null;

        try {
            // Arrange
            $admin  = $this->createAdmin();
            $target = User::factory()->create();
            config(['keystoneguru.super_admin_user_ids' => []]);
            $this->be($admin);

            // Act
            $response = $this->delete(sprintf('/admin/user/%d/delete', $target->id));

            // Assert
            $response->assertRedirect(route('admin.users'));
            $response->assertSessionHas('status', __('controller.user.flash.account_deleted_successfully'));
            $this->assertDatabaseMissing('users', ['id' => $target->id]);
        } finally {
            $target?->delete();
            $admin?->delete();
        }
    }

    #[Test]
    public function delete_givenAdminWhoIsNotASuperAdminDeletingAnAdmin_returnsForbidden(): void
    {
        $admin      = null;
        $superAdmin = null;
        $target     = null;

        try {
            // Arrange
            $admin      = $this->createAdmin();
            $superAdmin = $this->createAdmin();
            $target     = $this->createAdmin();
            config(['keystoneguru.super_admin_user_ids' => [$superAdmin->id]]);
            $this->be($admin);

            // Act
            $response = $this->delete(sprintf('/admin/user/%d/delete', $target->id));

            // Assert
            $response->assertForbidden();
            $this->assertDatabaseHas('users', ['id' => $target->id]);
        } finally {
            $target?->delete();
            $superAdmin?->delete();
            $admin?->delete();
        }
    }

    #[Test]
    public function delete_givenSuperAdminDeletingAnAdmin_deletesTheAdmin(): void
    {
        $superAdmin = null;
        $target     = null;

        try {
            // Arrange
            $superAdmin = $this->createAdmin();
            $target     = $this->createAdmin();
            config(['keystoneguru.super_admin_user_ids' => [$superAdmin->id]]);
            $this->be($superAdmin);

            // Act
            $response = $this->delete(sprintf('/admin/user/%d/delete', $target->id));

            // Assert
            $response->assertRedirect(route('admin.users'));
            $response->assertSessionHas('status', __('controller.user.flash.account_deleted_successfully'));
            $this->assertDatabaseMissing('users', ['id' => $target->id]);
        } finally {
            $target?->delete();
            $superAdmin?->delete();
        }
    }

    #[Test]
    public function delete_givenSuperAdminAsTarget_returnsForbidden(): void
    {
        $superAdmin = null;
        $target     = null;

        try {
            // Arrange
            $superAdmin = $this->createAdmin();
            $target     = $this->createAdmin();
            config(['keystoneguru.super_admin_user_ids' => [$superAdmin->id, $target->id]]);
            $this->be($superAdmin);

            // Act
            $response = $this->delete(sprintf('/admin/user/%d/delete', $target->id));

            // Assert
            $response->assertForbidden();
            $this->assertDatabaseHas('users', ['id' => $target->id]);
        } finally {
            $target?->delete();
            $superAdmin?->delete();
        }
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->addRole(Role::ROLE_USER);
        $admin->addRole(Role::ROLE_ADMIN);

        return $admin;
    }
}
