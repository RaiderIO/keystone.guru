<?php

namespace Tests\Feature\Controller;

use App\Email\PasswordChangedEmail;
use App\Events\UserColorChangedEvent;
use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameServerRegion;
use App\Models\Laratrust\Role;
use App\Models\LiveSession;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Service\DungeonRoute\CoverageServiceInterface;
use App\Service\Reverb\ReverbHttpApiServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;
use Mockery\Expectation;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use RuntimeException;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Profile')]
final class ProfileControllerTest extends PublicTestCase
{
    use ProvidesDungeon;

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
    public function update_givenAnEmptyRegion_keepsTheRegion(): void
    {
        $user = null;

        try {
            // Arrange
            $user = $this->userWithUserRole();
            $user->forceFill(['game_server_region_id' => GameServerRegion::ALL[GameServerRegion::EUROPE]])->save();

            // Act
            $response = $this->actingAs($user)->patch(sprintf('/profile/%d', $user->id), [
                'echo_color'            => '#abcdef',
                'timezone'              => 'Europe/Amsterdam',
                'game_server_region_id' => '',
            ]);

            // Assert
            $response->assertRedirect(route('profile.edit'));
            $this->assertSame(GameServerRegion::ALL[GameServerRegion::EUROPE], $user->refresh()->game_server_region_id);
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function update_givenAnEmailLongerThanItsColumn_returnsAnEmailError(): void
    {
        $user = null;

        try {
            // Arrange - a valid address, with labels and a local part each within their own limits
            $user  = $this->userWithUserRole();
            $email = sprintf('%s@%s.%s.%s.com', str_repeat('a', 64), str_repeat('b', 62), str_repeat('c', 62), str_repeat('d', 61));
            $this->assertSame(256, strlen($email));

            // Act
            $response = $this->actingAs($user)->patch(sprintf('/profile/%d', $user->id), [
                'email'      => $email,
                'echo_color' => '#abcdef',
                'timezone'   => 'Europe/Amsterdam',
            ]);

            // Assert
            $response->assertSessionHasErrors(['email']);
            $this->assertNotSame($email, $user->refresh()->email);
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
    public function updatePrivacy_givenTheBoxUnchecked_turnsTheSettingOff(): void
    {
        $user = null;

        try {
            // Arrange - an unchecked checkbox is not submitted at all
            $user = $this->userWithUserRole();
            $user->forceFill(['analytics_cookie_opt_out' => 1])->save();

            // Act
            $response = $this->actingAs($user)->patch(sprintf('/profile/%d/privacy', $user->id));

            // Assert
            $response->assertRedirect(route('profile.edit'));
            $this->assertSame(0, (int)$user->refresh()->analytics_cookie_opt_out);
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
            $response->assertRedirect(sprintf('%s#change-password', route('profile.edit')));
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
            $response->assertRedirect(sprintf('%s#change-password', route('profile.edit')));
            $response->assertSessionHasErrors(['new_password-confirm' => __('controller.profile.flash.new_passwords_do_not_match')]);
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
            $response->assertRedirect(sprintf('%s#change-password', route('profile.edit')));
            $response->assertSessionHasErrors(['new_password' => __('controller.profile.flash.new_password_equals_old_password')]);
            $response->assertSessionMissing('status');
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function changepassword_givenCorrectCurrentPassword_rotatesTheRememberToken(): void
    {
        $user = null;

        try {
            // Arrange
            $user = $this->userWithUserRole();
            User::query()->whereKey($user->id)->update(['remember_token' => 'token-of-a-remember-me-cookie']);

            // Act
            $response = $this->actingAs($user->refresh())->patch(route('profile.changepassword'), [
                'current_password'     => 'password',
                'new_password'         => 'a-brand-new-password',
                'new_password-confirm' => 'a-brand-new-password',
            ]);

            // Assert
            $response->assertSessionHasNoErrors();
            $rememberToken = (string)$user->fresh()?->remember_token;
            $this->assertNotSame('token-of-a-remember-me-cookie', $rememberToken);
            $this->assertSame(60, strlen($rememberToken));
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function changepassword_givenFailedChange_keepsTheRememberToken(): void
    {
        $user = null;

        try {
            // Arrange
            $user = $this->userWithUserRole();
            User::query()->whereKey($user->id)->update(['remember_token' => 'token-of-a-remember-me-cookie']);

            // Act
            $response = $this->actingAs($user->refresh())->patch(route('profile.changepassword'), [
                'current_password'     => 'not-the-password',
                'new_password'         => 'a-brand-new-password',
                'new_password-confirm' => 'a-brand-new-password',
            ]);

            // Assert
            $response->assertSessionHasErrors('current_password');
            $this->assertSame('token-of-a-remember-me-cookie', $user->fresh()?->remember_token);
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function changepassword_givenMoreAttemptsThanTheLimit_rejectsTheAttemptBeforeCheckingThePassword(): void
    {
        $user = null;
        $this->overrideHttpRateLimit(1);

        try {
            // Arrange
            $user = $this->userWithUserRole();

            // Act
            $firstResponse = $this->actingAs($user)->patch(route('profile.changepassword'), [
                'current_password'     => 'not-the-password',
                'new_password'         => 'a-brand-new-password',
                'new_password-confirm' => 'a-brand-new-password',
            ]);
            $secondResponse = $this->actingAs($user)->patch(route('profile.changepassword'), [
                'current_password'     => 'password',
                'new_password'         => 'a-brand-new-password',
                'new_password-confirm' => 'a-brand-new-password',
            ]);

            // Assert
            $firstResponse->assertRedirect(sprintf('%s#change-password', route('profile.edit')));
            $secondResponse->assertStatus(429);
            $this->assertTrue(Hash::check('password', (string)$user->fresh()?->password));
        } finally {
            $this->overrideHttpRateLimit(null);
            $user?->delete();
        }
    }

    #[Test]
    public function changepassword_givenCorrectCurrentPassword_queuesPasswordChangedEmailInTheUsersLocale(): void
    {
        $user = null;

        try {
            // Arrange
            Notification::fake();
            $user = $this->userWithUserRole();
            User::query()->whereKey($user->id)->update(['locale' => 'de_DE']);

            // Act
            $response = $this->actingAs($user->refresh())->patch(route('profile.changepassword'), [
                'current_password'     => 'password',
                'new_password'         => 'a-brand-new-password',
                'new_password-confirm' => 'a-brand-new-password',
            ]);

            // Assert
            $response->assertRedirect(sprintf('%s#change-password', route('profile.edit')));
            Notification::assertSentOnDemandTimes(PasswordChangedEmail::class, 1);
            Notification::assertSentOnDemand(
                PasswordChangedEmail::class,
                static fn(PasswordChangedEmail $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notification->locale === 'de_DE'
                    && $notifiable->routeNotificationFor('mail') === $user->email,
            );
            $this->assertInstanceOf(ShouldQueue::class, new PasswordChangedEmail());
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function changepassword_givenEmailChangedBeforeTheQueuedEmailIsSent_mailsTheAddressOfTheChange(): void
    {
        $user = null;

        try {
            // Arrange
            $queue         = Queue::fake();
            $user          = $this->userWithUserRole();
            $originalEmail = $user->email;

            // Act
            $this->actingAs($user)->patch(route('profile.changepassword'), [
                'current_password'     => 'password',
                'new_password'         => 'a-brand-new-password',
                'new_password-confirm' => 'a-brand-new-password',
            ]);
            User::query()->whereKey($user->id)->update(['email' => sprintf('hijacked-%s', $originalEmail)]);
            $jobs = $queue->pushed(SendQueuedNotifications::class);

            // Assert
            $this->assertCount(1, $jobs);
            /** @var SendQueuedNotifications $restoredJob */
            $restoredJob = unserialize(serialize($jobs->first()));
            $this->assertSame($originalEmail, $restoredJob->notifiables->first()->routeNotificationFor('mail', $restoredJob->notification));
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function changepassword_givenQueueingTheEmailFails_keepsTheOldPassword(): void
    {
        $user = null;

        try {
            // Arrange
            $user = $this->userWithUserRole();
            $this->mock(Dispatcher::class, static function (MockInterface $mock): void {
                /** @var Expectation $expectation */
                $expectation = $mock->shouldReceive('send');
                $expectation->andThrow(new RuntimeException('Queue unavailable'));
            });

            // Act
            $response = $this->actingAs($user)->patch(route('profile.changepassword'), [
                'current_password'     => 'password',
                'new_password'         => 'a-brand-new-password',
                'new_password-confirm' => 'a-brand-new-password',
            ]);

            // Assert
            $response->assertServerError();
            $this->assertTrue(Hash::check('password', (string)$user->fresh()?->password));
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    #[DataProvider('changepassword_givenFailedChange_sendsNoPasswordChangedEmail_dataProvider')]
    public function changepassword_givenFailedChange_sendsNoPasswordChangedEmail(
        string $currentPassword,
        string $newPassword,
        string $newPasswordConfirm,
        string $expectedErrorKey,
    ): void {
        $user = null;

        try {
            // Arrange
            Notification::fake();
            $user = $this->userWithUserRole();

            // Act
            $response = $this->actingAs($user)->patch(route('profile.changepassword'), [
                'current_password'     => $currentPassword,
                'new_password'         => $newPassword,
                'new_password-confirm' => $newPasswordConfirm,
            ]);

            // Assert
            $response->assertRedirect(sprintf('%s#change-password', route('profile.edit')));
            $response->assertSessionHasErrors($expectedErrorKey);
            Notification::assertSentOnDemandTimes(PasswordChangedEmail::class, 0);
            $this->assertTrue(Hash::check('password', (string)$user->fresh()?->password));
        } finally {
            $user?->delete();
        }
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function changepassword_givenFailedChange_sendsNoPasswordChangedEmail_dataProvider(): array
    {
        return [
            'wrong current password'       => ['not-the-password', 'a-brand-new-password', 'a-brand-new-password', 'current_password'],
            'mismatched confirmation'      => ['password', 'a-brand-new-password', 'another-new-password', 'new_password-confirm'],
            'current password reused'      => ['password', 'password', 'password', 'new_password'],
            'seven character new password' => ['password', 'seven77', 'seven77', 'new_password'],
        ];
    }

    #[Test]
    public function changepassword_givenEightCharacterNewPassword_changesThePassword(): void
    {
        $user = null;

        try {
            // Arrange
            $user = $this->userWithUserRole();

            // Act
            $response = $this->actingAs($user)->patch(route('profile.changepassword'), [
                'current_password'     => 'password',
                'new_password'         => 'eight888',
                'new_password-confirm' => 'eight888',
            ]);

            // Assert
            $response->assertSessionHasNoErrors();
            $this->assertTrue(Hash::check('eight888', (string)$user->fresh()?->password));
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function changepassword_givenEmptyFields_redirectsToThePasswordTabWithFieldErrors(): void
    {
        $user = null;

        try {
            // Arrange
            Notification::fake();
            $user = $this->userWithUserRole();

            // Act
            $response = $this->actingAs($user)->patch(route('profile.changepassword'), [
                'current_password'     => '',
                'new_password'         => '',
                'new_password-confirm' => '',
            ]);

            // Assert
            $response->assertRedirect(sprintf('%s#change-password', route('profile.edit')));
            $response->assertSessionHasErrors([
                'current_password'     => __('validation.custom.change_password.current_password_required'),
                'new_password'         => __('validation.custom.change_password.new_password_required'),
                'new_password-confirm' => __('validation.custom.change_password.new_password_confirm_required'),
            ]);
            Notification::assertSentOnDemandTimes(PasswordChangedEmail::class, 0);
            $this->assertTrue(Hash::check('password', (string)$user->fresh()?->password));
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function changepassword_givenFailedValidationWithNewPassword_neverFlashesTheNewPasswordToTheSession(): void
    {
        $user = null;

        try {
            // Arrange
            $user = $this->userWithUserRole();

            // Act
            $response = $this->actingAs($user)->patch(route('profile.changepassword'), [
                'username'             => $user->email,
                'current_password'     => '',
                'new_password'         => 'a-brand-new-password',
                'new_password-confirm' => 'a-brand-new-password',
            ]);

            // Assert
            $response->assertSessionHasErrors('current_password');
            $oldInput = session()->getOldInput();
            $this->assertSame($user->email, $oldInput['username'] ?? null);
            $this->assertArrayNotHasKey('new_password', $oldInput);
            $this->assertArrayNotHasKey('new_password-confirm', $oldInput);
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
    public function createTag_givenSameNameOwnedByAnotherUser_createsTheTag(): void
    {
        $user      = null;
        $otherUser = null;

        try {
            // Arrange - the name is only taken for the user who owns it
            $user      = $this->userWithUserRole();
            $otherUser = $this->userWithUserRole();
            $tagName   = sprintf('test-profile-tag-%s', fake()->uuid());
            $this->actingAs($otherUser)->post(route('profile.tag.create'), ['tag_name_new' => $tagName]);

            // Act
            $response = $this->actingAs($user)->post(route('profile.tag.create'), ['tag_name_new' => $tagName]);

            // Assert
            $response->assertSessionHas('status', __('controller.profile.flash.tag_created_successfully'));
            $this->assertSame(1, $this->personalTagCount($user, $tagName));
            $this->assertSame(1, $this->personalTagCount($otherUser, $tagName));
        } finally {
            foreach ([$user, $otherUser] as $createdUser) {
                if ($createdUser !== null) {
                    Tag::query()->where('context_class', User::class)->where('context_id', $createdUser->id)->delete();
                    $createdUser->delete();
                }
            }
        }
    }

    #[Test]
    public function routes_givenSeasonCookie_loadsTheCoverageOfThatSeason(): void
    {
        $user = null;

        try {
            // Arrange
            $user                                       = $this->userWithUserRole();
            $cookieSeason                               = Season::query()->where('id', '!=', app(SeasonServiceInterface::class)->getCurrentSeason()->id)->firstOrFail();
            $_COOKIE['dungeonroute_coverage_season_id'] = (string)$cookieSeason->id;

            $this->mock(CoverageServiceInterface::class, static function (MockInterface $mock) use ($user, $cookieSeason): void {
                $mock->expects('getForUser')
                    ->withArgs(static fn(User $forUser, Season $season) => $forUser->id === $user->id && $season->id === $cookieSeason->id)
                    ->andReturn(new Collection());
            });

            // Act
            $response = $this->actingAs($user)->get(route('profile.routes'));

            // Assert
            $response->assertOk();
        } finally {
            unset($_COOKIE['dungeonroute_coverage_season_id']);
            $user?->delete();
        }
    }

    #[Test]
    public function update_givenUserInRouteEditChannel_broadcastsTheColorChangeOnThatRoute(): void
    {
        $user  = null;
        $route = null;

        try {
            // Arrange
            $user  = $this->userWithUserRole();
            $route = $this->createRoute($user);
            $this->fakeReverbChannels(
                sprintf('presence-%s-route-edit.%s', config('app.type'), $route->public_key),
                $user,
            );
            Event::fake([UserColorChangedEvent::class]);

            // Act
            $this->actingAs($user)->patch(sprintf('/profile/%d', $user->id), $this->validUpdatePayload());

            // Assert
            Event::assertDispatched(
                UserColorChangedEvent::class,
                static fn(UserColorChangedEvent $event) => $event->broadcastOn()[0]->name === sprintf('presence-%s-route-edit.%s', config('app.type'), $route->public_key),
            );
        } finally {
            $route?->delete();
            $user?->delete();
        }
    }

    #[Test]
    public function update_givenUserInLiveSessionChannel_broadcastsTheColorChangeOnThatLiveSession(): void
    {
        $user        = null;
        $route       = null;
        $liveSession = null;

        try {
            // Arrange
            $user        = $this->userWithUserRole();
            $route       = $this->createRoute($user);
            $liveSession = LiveSession::create([
                'dungeon_route_id' => $route->id,
                'user_id'          => $user->id,
                'public_key'       => LiveSession::generateRandomPublicKey(),
            ]);
            $this->fakeReverbChannels(
                sprintf('presence-%s-live-session.%s', config('app.type'), $liveSession->public_key),
                $user,
            );
            Event::fake([UserColorChangedEvent::class]);

            // Act
            $this->actingAs($user)->patch(sprintf('/profile/%d', $user->id), $this->validUpdatePayload());

            // Assert
            Event::assertDispatched(
                UserColorChangedEvent::class,
                static fn(UserColorChangedEvent $event) => $event->broadcastOn()[0]->name === sprintf('presence-%s-live-session.%s', config('app.type'), $liveSession->public_key),
            );
        } finally {
            // Mass delete: LiveSession's "deleting" hook cascades into a table no migration creates
            if ($liveSession !== null) {
                LiveSession::query()->whereKey($liveSession->id)->delete();
            }
            $route?->delete();
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

    #[Test]
    public function createTag_givenANameLongerThanItsColumn_returnsAnErrorAndCreatesNothing(): void
    {
        $user = null;

        try {
            // Arrange
            $user    = $this->userWithUserRole();
            $tagName = str_repeat('a', 256);

            // Act
            $response = $this->actingAs($user)->post(route('profile.tag.create'), ['tag_name_new' => $tagName]);

            // Assert
            $response->assertSessionHasErrors(['tag_name_new']);
            $this->assertSame(0, $this->personalTagCount($user, $tagName));
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
     * @return array<string, mixed>
     */
    private function validUpdatePayload(): array
    {
        return [
            'echo_color'            => '#abcdef',
            'timezone'              => 'Europe/Amsterdam',
            'game_server_region_id' => 0,
        ];
    }

    private function createRoute(User $author): DungeonRoute
    {
        [$dungeon] = $this->findDungeon(facadeEnabled: false, minActiveFloors: 1, requireDefaultFloor: true);

        return DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'author_id'          => $author->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'expires_at'         => null,
        ]);
    }

    private function fakeReverbChannels(string $channelName, User $userInChannel): void
    {
        $this->mock(ReverbHttpApiServiceInterface::class, static function (MockInterface $mock) use ($channelName, $userInChannel): void {
            $mock->allows([
                'getChannels'     => [$channelName => []],
                'getChannelUsers' => [['id' => $userInChannel->id]],
            ]);
        });
    }

    /**
     * The profile routes sit behind `role:user|admin`, so a plain factory user would be rejected by
     * that middleware instead of by the policy - which would make the forbidden assertions above
     * pass for the wrong reason.
     */
    private function overrideHttpRateLimit(?int $limit): void
    {
        new ReflectionProperty(AppServiceProvider::class, 'rateLimitOverrideHttp')->setValue(null, $limit);
    }

    private function userWithUserRole(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }
}
