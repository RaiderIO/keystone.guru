<?php

namespace Tests\Unit\App\Models;

use App\Models\UserSocialLinkPlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
final class UserSocialLinkPlatformTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('normalizeProvider')]
    public function normalize_givenInput_returnsExpectedStoredValue(
        UserSocialLinkPlatform $platform,
        string                 $input,
        ?string                $expected,
    ): void {
        // Act
        $result = $platform->normalize($input);

        // Assert
        $this->assertSame($expected, $result);
    }

    /** @return array<string, array{0: UserSocialLinkPlatform, 1: string, 2: string|null}> */
    public static function normalizeProvider(): array
    {
        return [
            // Twitch
            'twitch username'                 => [UserSocialLinkPlatform::Twitch, 'someone', 'https://twitch.tv/someone'],
            'twitch username with whitespace' => [UserSocialLinkPlatform::Twitch, '  someone ', 'https://twitch.tv/someone'],
            'twitch at-handle'                => [UserSocialLinkPlatform::Twitch, '@someone', 'https://twitch.tv/someone'],
            'twitch full link'                => [UserSocialLinkPlatform::Twitch, 'https://www.twitch.tv/someone', 'https://twitch.tv/someone'],
            'twitch link without scheme'      => [UserSocialLinkPlatform::Twitch, 'twitch.tv/someone', 'https://twitch.tv/someone'],
            'twitch link with sub-page'       => [UserSocialLinkPlatform::Twitch, 'https://twitch.tv/someone/videos?filter=all', 'https://twitch.tv/someone'],
            'twitch http link is upgraded'    => [UserSocialLinkPlatform::Twitch, 'http://m.twitch.tv/someone', 'https://twitch.tv/someone'],
            'twitch link to another site'     => [UserSocialLinkPlatform::Twitch, 'https://evil.example.com/someone', null],
            'twitch lookalike host'           => [UserSocialLinkPlatform::Twitch, 'https://nottwitch.tv/someone', null],
            'twitch javascript payload'       => [UserSocialLinkPlatform::Twitch, 'javascript:alert(1)', null],
            'twitch username with space'      => [UserSocialLinkPlatform::Twitch, 'some one', null],
            'twitch link without a username'  => [UserSocialLinkPlatform::Twitch, 'https://twitch.tv/', null],
            'empty input'                     => [UserSocialLinkPlatform::Twitch, '   ', null],

            // YouTube
            'youtube bare handle gets an @'    => [UserSocialLinkPlatform::Youtube, 'someone', 'https://youtube.com/@someone'],
            'youtube at-handle'                => [UserSocialLinkPlatform::Youtube, '@someone', 'https://youtube.com/@someone'],
            'youtube handle link'              => [UserSocialLinkPlatform::Youtube, 'https://www.youtube.com/@someone/videos', 'https://youtube.com/@someone'],
            'youtube channel link'             => [UserSocialLinkPlatform::Youtube, 'https://youtube.com/channel/UCabcdefghijklmnopqrstuv', 'https://youtube.com/channel/UCabcdefghijklmnopqrstuv'],
            'youtube video link is no channel' => [UserSocialLinkPlatform::Youtube, 'https://www.youtube.com/watch?v=abc123', null],
            'youtube short link is no channel' => [UserSocialLinkPlatform::Youtube, 'https://youtu.be/abc123', null],

            // X
            'x username'            => [UserSocialLinkPlatform::X, '@someone', 'https://x.com/someone'],
            'x legacy twitter link' => [UserSocialLinkPlatform::X, 'https://twitter.com/someone', 'https://x.com/someone'],
            'x username too long'   => [UserSocialLinkPlatform::X, 'abcdefghijklmnop', null],

            // Bluesky
            'bluesky bare name'             => [UserSocialLinkPlatform::Bluesky, 'Someone', 'https://bsky.app/profile/someone.bsky.social'],
            'bluesky full handle'           => [UserSocialLinkPlatform::Bluesky, '@someone.bsky.social', 'https://bsky.app/profile/someone.bsky.social'],
            'bluesky custom domain handle'  => [UserSocialLinkPlatform::Bluesky, 'someone.com', 'https://bsky.app/profile/someone.com'],
            'bluesky profile link'          => [UserSocialLinkPlatform::Bluesky, 'https://bsky.app/profile/someone.bsky.social/post/abc', 'https://bsky.app/profile/someone.bsky.social'],
            'bluesky link to a non-profile' => [UserSocialLinkPlatform::Bluesky, 'https://bsky.app/search', null],

            // TikTok
            'tiktok username gets an @'    => [UserSocialLinkPlatform::Tiktok, 'someone', 'https://tiktok.com/@someone'],
            'tiktok profile link'          => [UserSocialLinkPlatform::Tiktok, 'https://www.tiktok.com/@some.one', 'https://tiktok.com/@some.one'],
            'tiktok link to a non-profile' => [UserSocialLinkPlatform::Tiktok, 'https://www.tiktok.com/explore', null],

            // Instagram
            'instagram username'     => [UserSocialLinkPlatform::Instagram, 'some.one', 'https://instagram.com/some.one'],
            'instagram profile link' => [UserSocialLinkPlatform::Instagram, 'https://www.instagram.com/some.one/', 'https://instagram.com/some.one'],

            // Patreon
            'patreon username' => [UserSocialLinkPlatform::Patreon, 'someone', 'https://patreon.com/someone'],
            'patreon c-link'   => [UserSocialLinkPlatform::Patreon, 'https://www.patreon.com/c/someone/posts', 'https://patreon.com/someone'],

            // Discord: a username is not a link, an invite is
            'discord username'               => [UserSocialLinkPlatform::Discord, 'Some.One_', 'some.one_'],
            'discord at-username'            => [UserSocialLinkPlatform::Discord, '@someone', 'someone'],
            'discord invite link'            => [UserSocialLinkPlatform::Discord, 'https://discord.gg/AbC123', 'https://discord.gg/AbC123'],
            'discord invite without scheme'  => [UserSocialLinkPlatform::Discord, 'discord.gg/AbC123', 'https://discord.gg/AbC123'],
            'discord.com invite link'        => [UserSocialLinkPlatform::Discord, 'https://discord.com/invite/AbC123', 'https://discord.gg/AbC123'],
            'discord link that is no invite' => [UserSocialLinkPlatform::Discord, 'https://discord.com/channels/1/2', null],
            'discord link to another site'   => [UserSocialLinkPlatform::Discord, 'https://evil.example.com/AbC123', null],
            'discord username with a space'  => [UserSocialLinkPlatform::Discord, 'some one', null],

            // Website: any https link, nothing else
            'website link'               => [UserSocialLinkPlatform::Website, 'https://example.com/me', 'https://example.com/me'],
            'website without scheme'     => [UserSocialLinkPlatform::Website, 'example.com/me', 'https://example.com/me'],
            'website plain http'         => [UserSocialLinkPlatform::Website, 'http://example.com', null],
            'website javascript payload' => [UserSocialLinkPlatform::Website, 'javascript:alert(1)', null],
            'website not a url'          => [UserSocialLinkPlatform::Website, 'not a url', null],
        ];
    }

    #[Test]
    #[DataProvider('toInputValueProvider')]
    public function toInputValue_givenStoredValue_returnsWhatTheFormShows(
        UserSocialLinkPlatform $platform,
        string                 $storedValue,
        string                 $expected,
    ): void {
        // Act
        $result = $platform->toInputValue($storedValue);

        // Assert
        $this->assertSame($expected, $result);
    }

    /** @return array<string, array{0: UserSocialLinkPlatform, 1: string, 2: string}> */
    public static function toInputValueProvider(): array
    {
        return [
            'twitch link shows the username'          => [UserSocialLinkPlatform::Twitch, 'https://twitch.tv/someone', 'someone'],
            'link stored before normalizing'          => [UserSocialLinkPlatform::Twitch, 'https://www.twitch.tv/someone/', 'someone'],
            'youtube handle keeps its @'              => [UserSocialLinkPlatform::Youtube, 'https://youtube.com/@someone', '@someone'],
            'value no longer accepted is shown as-is' => [UserSocialLinkPlatform::Youtube, 'https://youtu.be/abc123', 'https://youtu.be/abc123'],
            'discord username'                        => [UserSocialLinkPlatform::Discord, 'someone', 'someone'],
            'discord invite'                          => [UserSocialLinkPlatform::Discord, 'https://discord.gg/AbC123', 'https://discord.gg/AbC123'],
            'website'                                 => [UserSocialLinkPlatform::Website, 'https://example.com/me', 'https://example.com/me'],
        ];
    }

    #[Test]
    public function usernameUrlPrefix_givenEveryPlatformCase_isAnHttpsLinkToAnAllowedHost(): void
    {
        // Arrange
        $platformsWithPrefix = array_filter(
            UserSocialLinkPlatform::cases(),
            static fn(UserSocialLinkPlatform $platform): bool => $platform->usernameUrlPrefix() !== null,
        );

        // Assert
        $this->assertCount(count(UserSocialLinkPlatform::cases()) - 2, $platformsWithPrefix, 'Only Discord and the website take no username prefix');
        foreach ($platformsWithPrefix as $platform) {
            $this->assertTrue(
                $platform->isValidUrl(sprintf('%ssomeone', $platform->usernameUrlPrefix())),
                sprintf('The prefix of %s must produce a link its own host check accepts', $platform->value),
            );
        }
    }
}
