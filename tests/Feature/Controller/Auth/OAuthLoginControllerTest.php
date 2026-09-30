<?php

namespace Tests\Feature\Controller\Auth;

use App\Models\User;
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
            'script'                      => ['<script>alert(1)</script>', 'alert(1)'],
            'less than that is no tag'    => ['a < b', 'a < b'],
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

    private function mockSocialiteUser(string $driver, string $providerId, string $nickname): void
    {
        $socialiteUser = new SocialiteUser()->map([
            'id'       => $providerId,
            'nickname' => $nickname,
            'name'     => $nickname,
            'email'    => sprintf('%s@%s.test', $providerId, $driver),
        ]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with($driver)->andReturn($provider);
    }
}
