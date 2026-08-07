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
        $user = null;

        try {
            // Arrange
            $user = $this->userWithUserRole();

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
            $user?->delete();
        }
    }

    #[Test]
    public function update_givenAnotherUser_returnsForbidden(): void
    {
        $attacker = null;
        $victim   = null;

        try {
            // Arrange
            $attacker = $this->userWithUserRole();
            $victim   = $this->userWithUserRole();

            // Read the baseline back from the database: columns with a schema default are still null
            // on the in-memory model right after create(), which would make the comparison bogus
            $victim->refresh();
            $originalEmail    = $victim->email;
            $originalTimezone = $victim->timezone;

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
            $victim?->delete();
            $attacker?->delete();
        }
    }

    #[Test]
    public function update_givenAnotherUserAndATakenEmail_returnsForbiddenRatherThanAValidationError(): void
    {
        // ProfileFormRequest::authorize() has to reject before validation runs. rules() applies
        // `unique:users,email` while ignoring the route-bound user, so if validation ran first an
        // attacker could tell "this address belongs to the account I am probing" (403) apart from
        // "it belongs to some other account" (redirect carrying validation errors), turning the
        // endpoint into an email-to-account oracle.
        $attacker   = null;
        $victim     = null;
        $thirdParty = null;

        try {
            // Arrange
            $attacker   = $this->userWithUserRole();
            $victim     = $this->userWithUserRole();
            $thirdParty = $this->userWithUserRole();

            // Act - probe the victim with an address that is definitely taken by someone else
            $response = $this->actingAs($attacker)->patch(sprintf('/profile/%d', $victim->id), [
                'email'                 => $thirdParty->email,
                'echo_color'            => '#abcdef',
                'timezone'              => 'Europe/Amsterdam',
                'game_server_region_id' => 0,
            ]);

            // Assert - indistinguishable from probing with a free address
            $response->assertForbidden();
            $response->assertSessionHasNoErrors();
        } finally {
            $thirdParty?->delete();
            $victim?->delete();
            $attacker?->delete();
        }
    }

    #[Test]
    public function updatePrivacy_givenSelf_updatesTheSetting(): void
    {
        $user = null;

        try {
            // Arrange
            $user = $this->userWithUserRole();

            // Act
            $response = $this->actingAs($user)->patch(sprintf('/profile/%d/privacy', $user->id), [
                'analytics_cookie_opt_out' => 1,
            ]);

            // Assert
            $response->assertRedirect(route('profile.edit'));
            $this->assertSame(1, (int)$user->refresh()->analytics_cookie_opt_out);
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function updatePrivacy_givenAnotherUser_returnsForbidden(): void
    {
        $attacker = null;
        $victim   = null;

        try {
            // Arrange
            $attacker = $this->userWithUserRole();
            $victim   = $this->userWithUserRole();

            // See the note in update_givenAnotherUser_returnsForbidden - analytics_cookie_opt_out has
            // a schema default, so the in-memory value right after create() is not what is persisted
            $victim->refresh();
            $originalOptOut = $victim->analytics_cookie_opt_out;

            // Act
            $response = $this->actingAs($attacker)->patch(sprintf('/profile/%d/privacy', $victim->id), [
                'analytics_cookie_opt_out' => 1,
            ]);

            // Assert
            $response->assertForbidden();
            $this->assertSame($originalOptOut, $victim->refresh()->analytics_cookie_opt_out);
        } finally {
            $victim?->delete();
            $attacker?->delete();
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
