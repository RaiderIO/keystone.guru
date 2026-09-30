<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Models\Laratrust\Role;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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

    #[Test]
    public function changepassword_givenCorrectCurrentPassword_changesThePassword(): void
    {
        $user = null;

        try {
            // Arrange - the factory hashes the literal string 'password'
            $user = $this->userWithUserRole();

            // Act
            $response = $this->actingAs($user)->patch(route('profile.changepassword'), [
                'current_password'     => 'password',
                'new_password'         => 'a-brand-new-password',
                'new_password-confirm' => 'a-brand-new-password',
            ]);

            // Assert
            $response->assertOk();
            $response->assertSessionHas('status', __('controller.profile.flash.password_changed'));
            $this->assertTrue(Hash::check('a-brand-new-password', (string)$user->fresh()?->password));
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function changepassword_givenMismatchingNewPasswords_keepsThePasswordAndReportsTheMismatch(): void
    {
        $user = null;

        try {
            // Arrange
            $user = $this->userWithUserRole();

            // Act
            $response = $this->actingAs($user)->patch(route('profile.changepassword'), [
                'current_password'     => 'password',
                'new_password'         => 'a-brand-new-password',
                'new_password-confirm' => 'another-new-password',
            ]);

            // Assert
            $response->assertOk();
            $response->assertViewHas('errors', static fn($errors): bool => $errors->has('passwords_no_match'));
            $this->assertTrue(Hash::check('password', (string)$user->fresh()?->password));
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function changepassword_givenTheCurrentPasswordAsTheNewOne_reportsItAndKeepsThePassword(): void
    {
        $user = null;

        try {
            // Arrange
            $user = $this->userWithUserRole();

            // Act
            $response = $this->actingAs($user)->patch(route('profile.changepassword'), [
                'current_password'     => 'password',
                'new_password'         => 'password',
                'new_password-confirm' => 'password',
            ]);

            // Assert
            $response->assertOk();
            $response->assertViewHas('errors', static fn($errors): bool => $errors->has('passwords_match'));
            $response->assertSessionMissing('status');
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function delete_givenSelf_deletesTheAccountAndLogsOut(): void
    {
        $user = null;

        try {
            // Arrange
            $user = $this->userWithUserRole();

            // Act
            $response = $this->actingAs($user)->delete(route('profile.delete'));

            // Assert
            $response->assertRedirect(route('home'));
            $response->assertSessionHas('status', __('controller.profile.flash.account_deleted_successfully'));
            $this->assertFalse(User::query()->whereKey($user->id)->exists());
            $this->assertGuest();
        } finally {
            if ($user !== null) {
                User::query()->whereKey($user->id)->first()?->delete();
            }
        }
    }

    #[Test]
    public function delete_givenSiteAdmin_returnsForbiddenAndKeepsTheAccount(): void
    {
        $admin = null;

        try {
            // Arrange - a throwaway admin, so a regression cannot delete the seeded one
            $admin = User::factory()->create();
            $admin->addRole(Role::ROLE_ADMIN);

            // Act
            $response = $this->actingAs($admin)->delete(route('profile.delete'));

            // Assert
            $response->assertForbidden();
            $this->assertTrue(User::query()->whereKey($admin->id)->exists());
        } finally {
            if ($admin !== null) {
                $admin->roles()->sync([]);
                User::query()->whereKey($admin->id)->first()?->delete();
            }
        }
    }

    #[Test]
    public function createTag_givenNewName_createsAPersonalTag(): void
    {
        $user = null;

        try {
            // Arrange
            $user    = $this->userWithUserRole();
            $tagName = sprintf('test-profile-tag-%s', fake()->uuid());

            // Act
            $response = $this->actingAs($user)->post(route('profile.tag.create'), ['tag_name_new' => $tagName]);

            // Assert
            $response->assertRedirect(route('profile.tags'));
            $response->assertSessionHas('status', __('controller.profile.flash.tag_created_successfully'));
            $this->assertSame(1, $this->personalTagCount($user, $tagName));
        } finally {
            if ($user !== null) {
                Tag::query()->where('context_class', User::class)->where('context_id', $user->id)->delete();
            }
            $user?->delete();
        }
    }

    #[Test]
    public function createTag_givenExistingName_returnsAnErrorAndCreatesNoDuplicate(): void
    {
        $user = null;

        try {
            // Arrange
            $user    = $this->userWithUserRole();
            $tagName = sprintf('test-profile-tag-%s', fake()->uuid());
            $this->actingAs($user)->post(route('profile.tag.create'), ['tag_name_new' => $tagName]);

            // Act
            $response = $this->actingAs($user)->post(route('profile.tag.create'), ['tag_name_new' => $tagName]);

            // Assert
            $response->assertRedirect(route('profile.tags'));
            $response->assertSessionHasErrors(['tag_name_new' => __('controller.profile.flash.tag_already_exists')]);
            $this->assertSame(1, $this->personalTagCount($user, $tagName));
        } finally {
            if ($user !== null) {
                Tag::query()->where('context_class', User::class)->where('context_id', $user->id)->delete();
            }
            $user?->delete();
        }
    }

    private function personalTagCount(User $user, string $tagName): int
    {
        return Tag::query()
            ->where('context_class', User::class)
            ->where('context_id', $user->id)
            ->where('tag_category_id', TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL])
            ->where('name', $tagName)
            ->count();
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
