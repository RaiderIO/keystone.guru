<?php

namespace Tests\Unit\App\Models;

use App\Models\UserSocialLink;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
final class UserSocialLinkTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('urlProvider')]
    public function isValidUrlForPlatform_givenUrl_returnsExpectedResult(
        string $platform,
        string $url,
        bool   $expected,
        string $because,
    ): void {
        // Act
        $result = UserSocialLink::isValidUrlForPlatform($platform, $url);

        // Assert
        $this->assertSame($expected, $result, $because);
    }

    /** @return array<string, array{0: string, 1: string, 2: bool, 3: string}> */
    public static function urlProvider(): array
    {
        return [
            // Happy paths
            'twitch channel' => [
                UserSocialLink::PLATFORM_TWITCH, 'https://twitch.tv/someone', true,
                'A plain https twitch.tv link is the common case',
            ],
            'twitch with www subdomain' => [
                UserSocialLink::PLATFORM_TWITCH, 'https://www.twitch.tv/someone', true,
                'www is a subdomain of the allowed host and must be accepted',
            ],
            'youtube alternate host' => [
                UserSocialLink::PLATFORM_YOUTUBE, 'https://youtu.be/abc123', true,
                'youtu.be is listed alongside youtube.com for this platform',
            ],
            'x still accepts twitter.com' => [
                UserSocialLink::PLATFORM_X, 'https://twitter.com/someone', true,
                'Legacy twitter.com links must keep working after the rename',
            ],
            'website accepts any https host' => [
                UserSocialLink::PLATFORM_WEBSITE, 'https://example.com/me', true,
                'The website link is intentionally unconstrained beyond requiring https',
            ],

            // Scheme rejections - the reason the allowlist exists
            'javascript payload' => [
                UserSocialLink::PLATFORM_WEBSITE, 'javascript:alert(1)', false,
                'A javascript: URI must never survive validation - it would run on the public profile',
            ],
            'data payload' => [
                UserSocialLink::PLATFORM_WEBSITE, 'data:text/html;base64,PHNjcmlwdD4=', false,
                'data: URIs are another script-execution vector and are not https',
            ],
            'plain http' => [
                UserSocialLink::PLATFORM_TWITCH, 'http://twitch.tv/someone', false,
                'Only https is accepted, even for an otherwise allowed host',
            ],
            'protocol relative' => [
                UserSocialLink::PLATFORM_TWITCH, '//twitch.tv/someone', false,
                'Without an explicit https scheme the URL is rejected',
            ],

            // Host rejections - the suffix-matching traps
            'host merely ending in the allowed name' => [
                UserSocialLink::PLATFORM_TWITCH, 'https://nottwitch.tv/someone', false,
                'nottwitch.tv must not pass as twitch.tv - matching is on a dot-prefixed suffix',
            ],
            'allowed host as a subdomain of an attacker domain' => [
                UserSocialLink::PLATFORM_TWITCH, 'https://twitch.tv.evil.com/someone', false,
                'The allowed host appearing as a prefix of another domain must not pass',
            ],
            'wrong platform for the host' => [
                UserSocialLink::PLATFORM_TWITCH, 'https://youtube.com/@someone', false,
                'A YouTube link submitted under the Twitch field is rejected',
            ],

            // Malformed / unknown
            'unknown platform' => [
                'myspace', 'https://myspace.com/someone', false,
                'A platform outside the allowlist is rejected regardless of the URL',
            ],
            'not a url at all' => [
                UserSocialLink::PLATFORM_TWITCH, 'not a url', false,
                'Input without a scheme and host cannot be validated and is rejected',
            ],
            'empty string' => [
                UserSocialLink::PLATFORM_TWITCH, '', false,
                'An empty string has no scheme or host',
            ],
        ];
    }

    #[Test]
    public function getIconClass_givenEveryKnownPlatform_returnsANonEmptyClass(): void
    {
        // Arrange & Act & Assert
        foreach (UserSocialLink::ALL as $platform) {
            $socialLink           = new UserSocialLink();
            $socialLink->platform = $platform;

            $this->assertNotEmpty(
                $socialLink->getIconClass(),
                sprintf('Platform %s must map to an icon class', $platform),
            );
        }
    }

    #[Test]
    public function getIconClass_givenUnknownPlatform_fallsBackToTheWebsiteIcon(): void
    {
        // Arrange
        $socialLink           = new UserSocialLink();
        $socialLink->platform = 'something-we-removed-later';

        // Act
        $result = $socialLink->getIconClass();

        // Assert
        $this->assertSame(
            UserSocialLink::PLATFORM_ICONS[UserSocialLink::PLATFORM_WEBSITE],
            $result,
            'An unknown platform must not render a broken icon',
        );
    }

    #[Test]
    public function platformConstants_areConsistentAcrossTheLookupTables(): void
    {
        // Act & Assert
        foreach (UserSocialLink::ALL as $platform) {
            $this->assertArrayHasKey(
                $platform,
                UserSocialLink::PLATFORM_HOSTS,
                sprintf('Platform %s is missing a host allowlist entry', $platform),
            );
            $this->assertArrayHasKey(
                $platform,
                UserSocialLink::PLATFORM_ICONS,
                sprintf('Platform %s is missing an icon entry', $platform),
            );
        }
    }
}
