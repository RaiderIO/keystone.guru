<?php

namespace Tests\Feature\Controller;

use App\Models\Laratrust\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Profile')]
final class ProfileControllerTest extends PublicTestCase
{
    #[Test]
    public function update_givenSelf_updatesTheProfile(): void
    {
        // Arrange
        $user = $this->userWithUserRole();

        try {
            // Act
            $response = $this->actingAs($user)->patch(sprintf('/profile/%d', $user->id), [
                'echo_color'            => '#abcdef',
                'timezone'              => 'Europe/Amsterdam',
                'game_server_region_id' => 0,
            ]);

            // Assert
            $response->assertRedirect(route('profile.edit'));

            $user->refresh();
            $this->assertSame('#abcdef', $user->echo_color);
            $this->assertSame('Europe/Amsterdam', $user->timezone);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function update_givenAnotherUser_returnsForbidden(): void
    {
        // Arrange
        $attacker = $this->userWithUserRole();
        $victim   = $this->userWithUserRole();

        // Read the baseline back from the database: columns with a schema default are still null on
        // the in-memory model right after create(), which would make the comparison below bogus
        $victim->refresh();
        $originalEmail    = $victim->email;
        $originalTimezone = $victim->timezone;

        try {
            // Act - a non-OAuth account is the dangerous case: update() writes $validated['email'],
            // so an unguarded route lets an attacker point a victim's account at their own inbox
            // and take it over with a password reset
            $response = $this->actingAs($attacker)->patch(sprintf('/profile/%d', $victim->id), [
                'email'                 => sprintf('attacker_%s@example.com', fake()->uuid()),
                'echo_color'            => '#abcdef',
                'timezone'              => 'Europe/Amsterdam',
                'game_server_region_id' => 0,
            ]);

            // Assert
            $response->assertForbidden();

            $victim->refresh();
            $this->assertSame($originalEmail, $victim->email);
            $this->assertSame($originalTimezone, $victim->timezone);
        } finally {
            $victim->delete();
            $attacker->delete();
        }
    }

    #[Test]
    public function updatePrivacy_givenSelf_updatesTheSetting(): void
    {
        // Arrange
        $user = $this->userWithUserRole();

        try {
            // Act
            $response = $this->actingAs($user)->patch(sprintf('/profile/%d/privacy', $user->id), [
                'analytics_cookie_opt_out' => 1,
            ]);

            // Assert
            $response->assertRedirect(route('profile.edit'));
            $this->assertSame(1, (int)$user->refresh()->analytics_cookie_opt_out);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function updatePrivacy_givenAnotherUser_returnsForbidden(): void
    {
        // Arrange
        $attacker = $this->userWithUserRole();
        $victim   = $this->userWithUserRole();

        // See the note in update_givenAnotherUser_returnsForbidden - analytics_cookie_opt_out has a
        // schema default, so the in-memory value right after create() is not what is persisted
        $victim->refresh();
        $originalOptOut = $victim->analytics_cookie_opt_out;

        try {
            // Act
            $response = $this->actingAs($attacker)->patch(sprintf('/profile/%d/privacy', $victim->id), [
                'analytics_cookie_opt_out' => 1,
            ]);

            // Assert
            $response->assertForbidden();
            $this->assertSame($originalOptOut, $victim->refresh()->analytics_cookie_opt_out);
        } finally {
            $victim->delete();
            $attacker->delete();
        }
    }

    /**
     * The profile routes sit behind `role:user|admin`, so a plain factory user would be rejected by
     * that middleware instead of by the policy - which would make the forbidden assertions above
     * pass for the wrong reason.
     */
    private function userWithUserRole(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }
}
