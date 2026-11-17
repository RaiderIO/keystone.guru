<?php

namespace Tests\Feature\Controller\Auth;

use App\Http\Controllers\Auth\RegisterController;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Auth')]
final class OAuthLoginControllerTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('handleProviderCallback_nickname_dataProvider')]
    public function handleProviderCallback_givenANewDiscordUser_storesTheNicknameWithoutHtml(string $nickname, string $expectedName): void
    {
        // Arrange
        $providerId = sprintf('%d', random_int(100000000, 999999999));
        $oAuthId    = sprintf('%s@discord', $providerId);
        $suffix     = sprintf('%d', random_int(100000, 999999));

        $this->mockSocialiteUser('discord', $providerId, $nickname . $suffix);

        try {
            // Act
            $response = $this->get(route('login.discord.callback'));

            // Assert
            $response->assertRedirect();
            /** @var User|null $user */
            $user = User::query()->where('oauth_id', $oAuthId)->first();
            $this->assertNotNull($user, 'The OAuth callback did not create the user');
            $this->assertSame($expectedName . $suffix, $user->name);
            $this->assertSame($expectedName . $suffix, $user->fresh()?->name);
        } finally {
            User::query()->where('oauth_id', $oAuthId)->first()?->delete();
        }
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function handleProviderCallback_nickname_dataProvider(): array
    {
        return [
            'plain nickname'              => ['Wotuu', 'Wotuu'],
            'bold nickname'               => ['<b>Wotuu</b>', 'Wotuu'],
            'image with an event handler' => ['<img src=x onerror=alert(1)>Wotuu', 'Wotuu'],
            'script'                      => ['<script>alert(1)</script>', 'alert-1-'],
            'less than that is no tag'    => ['a < b', 'a-b'],
        ];
    }

    #[Test]
    public function handleProviderCallback_givenNicknameWhoseSlugIsTaken_createsTheUserWithASuffixedSlug(): void
    {
        // Arrange
        $number     = random_int(100000, 999999);
        $providerId = sprintf('%d', random_int(100000000, 999999999));
        $oAuthId    = sprintf('%s@discord', $providerId);
        $slugOwner  = null;

        $this->mockSocialiteUser('discord', $providerId, sprintf('woe2#%d', $number));

        try {
            $slugOwner = User::factory()->create(['name' => sprintf('woe2 %d', $number)]);

            // Act
            $response = $this->get(route('login.discord.callback'));

            // Assert
            $response->assertRedirect();
            /** @var User|null $user */
            $user = User::query()->where('oauth_id', $oAuthId)->first();
            $this->assertNotNull($user, 'The OAuth callback did not create the user');
            $this->assertSame(sprintf('woe2-%d', $number), $slugOwner->slug);
            $this->assertSame(sprintf('woe2-%d-2', $number), $user->slug);
            $this->get(route('profile.view', ['user' => $slugOwner]))->assertOk();
            $this->get(route('profile.view', ['user' => $user]))->assertOk();
        } finally {
            User::query()->where('oauth_id', $oAuthId)->first()?->delete();
            $slugOwner?->delete();
        }
    }

    #[Test]
    public function handleProviderCallback_givenTheEmailOfAnExistingAccount_createsNoUserAndLogsNobodyIn(): void
    {
        // Arrange
        $providerId   = sprintf('%d', random_int(100000000, 999999999));
        $oAuthId      = sprintf('%s@discord', $providerId);
        $existingUser = null;

        try {
            $existingUser = User::factory()->create(['email' => sprintf('%s@discord.test', $providerId)]);
            $this->mockSocialiteUser('discord', $providerId, sprintf('Newcomer%d', random_int(100000, 999999)));

            // Act
            $response = $this->get(route('login.discord.callback'));

            // Assert
            $response->assertRedirect('/');
            $response->assertSessionHas('warning', sprintf(__('controller.oauthlogin.flash.email_exists'), $existingUser->email));
            $this->assertGuest();
            $this->assertFalse(User::query()->where('oauth_id', $oAuthId)->exists());
        } finally {
            User::query()->where('oauth_id', $oAuthId)->first()?->delete();
            $existingUser?->delete();
        }
    }

    #[Test]
    public function handleProviderCallback_givenTheNameOfAnExistingAccount_createsNoUserAndLogsNobodyIn(): void
    {
        // Arrange
        $providerId   = sprintf('%d', random_int(100000000, 999999999));
        $oAuthId      = sprintf('%s@discord', $providerId);
        $existingUser = null;

        try {
            $existingUser = User::factory()->create(['name' => sprintf('Taken%d', random_int(100000, 999999))]);
            $this->mockSocialiteUser('discord', $providerId, $existingUser->name);

            // Act
            $response = $this->get(route('login.discord.callback'));

            // Assert
            $response->assertRedirect('/');
            $response->assertSessionHas('warning', sprintf(__('controller.oauthlogin.flash.user_exists'), $existingUser->name));
            $this->assertGuest();
            $this->assertFalse(User::query()->where('oauth_id', $oAuthId)->exists());
        } finally {
            User::query()->where('oauth_id', $oAuthId)->first()?->delete();
            $existingUser?->delete();
        }
    }

    #[Test]
    public function handleProviderCallback_givenAReturningOAuthUser_logsThemInWithoutCreatingAnotherUser(): void
    {
        // Arrange
        $providerId   = sprintf('%d', random_int(100000000, 999999999));
        $oAuthId      = sprintf('%s@discord', $providerId);
        $existingUser = null;

        try {
            $existingUser = User::factory()->create(['oauth_id' => $oAuthId, 'password' => '']);
            $this->mockSocialiteUser('discord', $providerId, sprintf('Returning%d', random_int(100000, 999999)));

            // Act
            $response = $this->get(route('login.discord.callback'));

            // Assert
            $response->assertRedirect('/');
            $this->assertAuthenticatedAs($existingUser);
            $this->assertSame(1, User::query()->where('oauth_id', $oAuthId)->count());
        } finally {
            auth()->logout();
            $existingUser?->delete();
        }
    }

    #[Test]
    public function handleProviderCallback_givenADiscordUserWithAVerifiedEmail_createsTheUserWithThatEmailAndLogsThemIn(): void
    {
        // Arrange
        $providerId = sprintf('%d', random_int(100000000, 999999999));
        $oAuthId    = sprintf('%s@discord', $providerId);
        $email      = sprintf('verified%s@discord.test', $providerId);

        $this->mockSocialiteUser('discord', $providerId, sprintf('Verified%d', random_int(100000, 999999)), ['email' => $email], ['verified' => true]);

        try {
            // Act
            $response = $this->get(route('login.discord.callback'));

            // Assert
            $response->assertRedirect();
            /** @var User|null $user */
            $user = User::query()->where('oauth_id', $oAuthId)->first();
            $this->assertNotNull($user, 'The OAuth callback did not create the user');
            $this->assertSame($email, $user->email);
            $this->assertAuthenticatedAs($user);
        } finally {
            auth()->logout();
            User::query()->where('oauth_id', $oAuthId)->first()?->delete();
        }
    }

    #[Test]
    #[DataProvider('handleProviderCallback_unverifiedRaw_dataProvider')]
    public function handleProviderCallback_givenADiscordUserWithAnUnverifiedEmail_createsNoUserAndLogsNobodyIn(array $raw): void
    {
        // Arrange
        $providerId = sprintf('%d', random_int(100000000, 999999999));
        $oAuthId    = sprintf('%s@discord', $providerId);
        $email      = sprintf('unverified%s@discord.test', $providerId);

        $this->mockSocialiteUser('discord', $providerId, sprintf('Unverified%d', random_int(100000, 999999)), ['email' => $email], $raw);

        try {
            // Act
            $response = $this->get(route('login.discord.callback'));

            // Assert
            $response->assertRedirect('/');
            $response->assertSessionHas('warning', __('controller.oauthlogin.flash.email_not_verified'));
            $this->assertGuest();
            $this->assertFalse(User::query()->where('oauth_id', $oAuthId)->exists());
            $this->assertFalse(User::query()->where('email', $email)->exists());
        } finally {
            User::query()->where('oauth_id', $oAuthId)->first()?->delete();
        }
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function handleProviderCallback_unverifiedRaw_dataProvider(): array
    {
        return [
            'verified false'   => [['verified' => false]],
            'verified missing' => [[]],
            'verified truthy'  => [['verified' => 1]],
        ];
    }

    #[Test]
    public function handleProviderCallback_givenAnUnverifiedEmailOfAnExistingAccount_leavesThatAccountAloneAndLogsNobodyIn(): void
    {
        // Arrange
        $providerId   = sprintf('%d', random_int(100000000, 999999999));
        $oAuthId      = sprintf('%s@discord', $providerId);
        $existingUser = null;

        try {
            $existingUser = User::factory()->create(['email' => sprintf('owner%s@discord.test', $providerId)]);
            $this->mockSocialiteUser('discord', $providerId, sprintf('Claimer%d', random_int(100000, 999999)), ['email' => $existingUser->email], ['verified' => false]);

            // Act
            $response = $this->get(route('login.discord.callback'));

            // Assert
            $response->assertRedirect('/');
            $response->assertSessionHas('warning', __('controller.oauthlogin.flash.email_not_verified'));
            $this->assertGuest();
            $this->assertFalse(User::query()->where('oauth_id', $oAuthId)->exists());
            $this->assertNotSame($oAuthId, $existingUser->fresh()?->oauth_id);
        } finally {
            User::query()->where('oauth_id', $oAuthId)->first()?->delete();
            $existingUser?->delete();
        }
    }

    #[Test]
    public function handleProviderCallback_givenADiscordUserWithoutAnEmail_createsTheUserWithAPlaceholderEmail(): void
    {
        // Arrange
        $providerId = sprintf('%d', random_int(100000000, 999999999));
        $oAuthId    = sprintf('%s@discord', $providerId);

        $this->mockSocialiteUser('discord', $providerId, sprintf('NoEmail%d', random_int(100000, 999999)), ['email' => null], []);

        try {
            // Act
            $response = $this->get(route('login.discord.callback'));

            // Assert
            $response->assertRedirect();
            /** @var User|null $user */
            $user = User::query()->where('oauth_id', $oAuthId)->first();
            $this->assertNotNull($user, 'The OAuth callback did not create the user');
            $this->assertSame(sprintf('%s@discordapp.com', $providerId), $user->email);
            $this->assertAuthenticatedAs($user);
        } finally {
            auth()->logout();
            User::query()->where('oauth_id', $oAuthId)->first()?->delete();
        }
    }

    #[Test]
    #[DataProvider('handleProviderCallback_nameFormat_dataProvider')]
    public function handleProviderCallback_givenANameOutsideTheRegisterRules_storesItNormalised(string $nicknameFormat, string $expectedFormat): void
    {
        // Arrange
        $providerId = sprintf('%d', random_int(100000000, 999999999));
        $oAuthId    = sprintf('%s@battlenet', $providerId);
        $token      = sprintf('%d', random_int(100000, 999999));

        $this->mockSocialiteUser('battlenet', $providerId, sprintf($nicknameFormat, $token));

        try {
            // Act
            $response = $this->get(route('login.battlenet.callback'));

            // Assert
            $response->assertRedirect();
            /** @var User|null $user */
            $user = User::query()->where('oauth_id', $oAuthId)->first();
            $this->assertNotNull($user, 'The OAuth callback did not create the user');
            $this->assertSame(sprintf($expectedFormat, $token), $user->name);
            $this->assertTrue(Validator::make(['name' => $user->name], ['name' => RegisterController::NAME_FORMAT_RULES])->passes());
        } finally {
            User::query()->where('oauth_id', $oAuthId)->first()?->delete();
        }
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function handleProviderCallback_nameFormat_dataProvider(): array
    {
        return [
            'battletag'                 => ['Wotuu%s#1234', 'Wotuu%s-1234'],
            'spaces and punctuation'    => ['  John %s Smith!! ', 'John-%s-Smith'],
            'accented letters are kept' => ['Zoë_%s-é', 'Zoë_%s-é'],
            'too long'                  => [sprintf('%%s%s', str_repeat('x', 40)), sprintf('%%s%s', str_repeat('x', 26))],
        ];
    }

    #[Test]
    public function handleProviderCallback_givenANameWithNoUsableCharacters_storesANameDerivedFromTheProviderId(): void
    {
        // Arrange
        $providerId = sprintf('%d', random_int(100000000, 999999999));
        $oAuthId    = sprintf('%s@discord', $providerId);

        $this->mockSocialiteUser('discord', $providerId, '!!! ???');

        try {
            // Act
            $response = $this->get(route('login.discord.callback'));

            // Assert
            $response->assertRedirect();
            /** @var User|null $user */
            $user = User::query()->where('oauth_id', $oAuthId)->first();
            $this->assertNotNull($user, 'The OAuth callback did not create the user');
            $this->assertSame(sprintf('discord-%s', $providerId), $user->name);
        } finally {
            User::query()->where('oauth_id', $oAuthId)->first()?->delete();
        }
    }

    #[Test]
    public function handleProviderCallback_givenAnInvalidState_redirectsHomeWithAWarning(): void
    {
        // Arrange
        $provider = $this->createMockPublic(Provider::class);
        $provider->method('user')->willThrowException(new InvalidStateException());
        Socialite::shouldReceive('driver')->with('discord')->andReturn($provider);

        // Act
        $response = $this->get(route('login.discord.callback'));

        // Assert
        $response->assertRedirect('/');
        $response->assertSessionHas('warning', __('controller.oauthlogin.flash.permission_denied'));
        $this->assertGuest();
    }

    /**
     * @param array<string, mixed> $attributes Overrides of the mapped user attributes
     * @param array<string, mixed> $raw        The provider's raw user payload
     */
    private function mockSocialiteUser(
        string $driver,
        string $providerId,
        string $nickname,
        array  $attributes = [],
        array  $raw = ['verified' => true],
    ): void {
        $socialiteUser = new SocialiteUser()->setRaw($raw)->map(array_merge([
            'id'       => $providerId,
            'nickname' => $nickname,
            'name'     => $nickname,
            'email'    => sprintf('%s@%s.test', $providerId, $driver),
        ], $attributes));

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with($driver)->andReturn($provider);
    }
}
