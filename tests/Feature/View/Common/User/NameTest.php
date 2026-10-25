<?php

namespace Tests\Feature\View\Common\User;

use App\Models\Laratrust\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCases\PublicTestCase;

#[Group('BetaTesterRole')]
final class NameTest extends PublicTestCase
{
    #[Test]
    public function render_givenInternalTeamUser_showsRaiderIOStaff(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            $user->addRole(Role::ROLE_INTERNAL_TEAM);

            // Act
            $result = view('common.user.name', ['user' => $user, 'link' => true])->render();

            // Assert
            $this->assertStringContainsString('raider_io_staff_text', $result);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    #[TestWith([Role::ROLE_BETA_TESTER])]
    #[TestWith([Role::ROLE_USER])]
    public function render_givenNonStaffUser_showsTheirOwnName(string $role): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            $user->addRole($role);

            // Act
            $result = view('common.user.name', ['user' => $user, 'link' => true])->render();

            // Assert
            $this->assertStringNotContainsString('raider_io_staff_text', $result);
            $this->assertStringContainsString(e($user->name), $result);
        } finally {
            $user->delete();
        }
    }
}
