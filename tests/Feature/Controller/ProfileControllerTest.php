<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Models\Laratrust\Role;
use App\Models\User;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Profile')]
final class ProfileControllerTest extends PublicTestCase
{
    #[Test]
    public function tags_givenCreatorProfilesActive_pointsAtCollectionsForSharing(): void
    {
        $user = null;

        try {
            // Arrange
            $user = $this->userWithUserRole();
            Feature::for($user)->activate(CreatorProfiles::class);

            // Act
            $response = $this->actingAs($user)->get(route('profile.tags'));

            // Assert
            $response->assertOk();
            $response->assertSee(__('view_profile.tags.link_collections'));
            $response->assertSee(route('collections.index'));
        } finally {
            if ($user !== null) {
                Feature::for($user)->forget(CreatorProfiles::class);
            }
            $user?->delete();
        }
    }

    #[Test]
    public function tags_givenCreatorProfilesInactive_hidesTheCollectionsPointer(): void
    {
        $user = null;

        try {
            // Arrange - /collections is behind the same feature, so the pointer would link to a 404
            $user = $this->userWithUserRole();
            Feature::for($user)->deactivate(CreatorProfiles::class);

            // Act
            $response = $this->actingAs($user)->get(route('profile.tags'));

            // Assert
            $response->assertOk();
            $response->assertDontSee(__('view_profile.tags.link_collections'));
        } finally {
            if ($user !== null) {
                Feature::for($user)->forget(CreatorProfiles::class);
            }
            $user?->delete();
        }
    }

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
    public function update_givenNameWhoseSlugIsTaken_returnsNameError(): void
    {
        $user      = null;
        $slugOwner = null;

        try {
            // Arrange
            $number         = random_int(100000, 999999);
            $user           = $this->userWithUserRole();
            $user->password = '';
            $user->save();
            $slugOwner = User::factory()->create(['name' => sprintf('woe2#%d', $number)]);

            // Act
            $response = $this->actingAs($user)->patch(sprintf('/profile/%d', $user->id), [
                'name'                  => sprintf('woe2-%d', $number),
                'echo_color'            => '#abcdef',
                'timezone'              => 'Europe/Amsterdam',
                'game_server_region_id' => 0,
            ]);

            // Assert
            $response->assertSessionHasErrors(['name' => __('rules.user_slug_available_rule.taken')]);
            $this->assertNotSame(sprintf('woe2-%d', $number), $user->fresh()?->name);
        } finally {
            $user?->delete();
            $slugOwner?->delete();
        }
    }

    #[Test]
    public function update_givenUnchangedNameHoldingASuffixedSlug_updatesTheProfile(): void
    {
        $user      = null;
        $slugOwner = null;

        try {
            // Arrange
            $number         = random_int(100000, 999999);
            $slugOwner      = User::factory()->create(['name' => sprintf('foo#bar%d', $number)]);
            $user           = $this->userWithUserRole();
            $user->name     = sprintf('foo-bar%d', $number);
            $user->password = '';
            $user->save();

            // Act
            $response = $this->actingAs($user)->patch(sprintf('/profile/%d', $user->id), [
                'name'                  => $user->name,
                'echo_color'            => '#abcdef',
                'timezone'              => 'Europe/Amsterdam',
                'game_server_region_id' => 0,
            ]);

            // Assert
            $this->assertSame(sprintf('foo-bar%d-2', $number), $user->slug);
            $response->assertSessionHasNoErrors();
            $response->assertRedirect(route('profile.edit'));
            $refreshedUser = User::findOrFail($user->id);
            $this->assertSame('Europe/Amsterdam', $refreshedUser->timezone);
            $this->assertSame(sprintf('foo-bar%d-2', $number), $refreshedUser->slug);
        } finally {
            $user?->delete();
            $slugOwner?->delete();
        }
    }

    #[Test]
    public function update_givenNewName_movesTheProfileToTheNewSlug(): void
    {
        $user = null;

        try {
            // Arrange - only OAuth accounts may change their username
            $user           = $this->userWithUserRole();
            $user->password = '';
            $user->save();
            $oldSlug = $user->slug;
            $newName = sprintf('Renamed%d', random_int(100000, 999999));

            // Act
            $response = $this->actingAs($user)->patch(sprintf('/profile/%d', $user->id), [
                'name'                  => $newName,
                'echo_color'            => '#abcdef',
                'timezone'              => 'Europe/Amsterdam',
                'game_server_region_id' => 0,
            ]);

            // Assert
            $response->assertRedirect(route('profile.edit'));
            $this->assertSame(mb_strtolower($newName), $user->fresh()?->slug);
            $this->get(sprintf('/user/%s', $oldSlug))->assertNotFound();
        } finally {
            $user?->delete();
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
