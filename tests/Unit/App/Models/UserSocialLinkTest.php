<?php

namespace Tests\Unit\App\Models;

use App\Models\UserSocialLink;
use App\Models\UserSocialLinkPlatform;
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
        ?string $platform,
        string  $url,
        bool    $expected,
        string  $because,
    ): void {
        // Act
        $result = UserSocialLink::isValidUrlForPlatform($platform ?? 'myspace', $url);

        // Assert
        $this->assertSame($expected, $result, $because);
    }

    /** @return array<string, array{0: string|null, 1: string, 2: bool, 3: string}> */
    public static function urlProvider(): array
    {
        return [
            // Happy paths
            'twitch channel' => [
                UserSocialLinkPlatform::Twitch->value, 'https://twitch.tv/someone', true,
                'A plain https twitch.tv link is the common case',
            ],
            'twitch with www subdomain' => [
                UserSocialLinkPlatform::Twitch->value, 'https://www.twitch.tv/someone', true,
                'www is a subdomain of the allowed host and must be accepted',
            ],
            'youtube alternate host' => [
                UserSocialLinkPlatform::Youtube->value, 'https://youtu.be/abc123', true,
                'youtu.be is listed alongside youtube.com for this platform',
            ],
            'x still accepts twitter.com' => [
                UserSocialLinkPlatform::X->value, 'https://twitter.com/someone', true,
                'Legacy twitter.com links must keep working after the rename',
            ],
            'website accepts any https host' => [
                UserSocialLinkPlatform::Website->value, 'https://example.com/me', true,
                'The website link is intentionally unconstrained beyond requiring https',
            ],

            // Scheme rejections - the reason the allowlist exists
            'javascript payload' => [
                UserSocialLinkPlatform::Website->value, 'javascript:alert(1)', false,
                'A javascript: URI must never survive validation - it would run on the public profile',
            ],
            'data payload' => [
                UserSocialLinkPlatform::Website->value, 'data:text/html;base64,PHNjcmlwdD4=', false,
                'data: URIs are another script-execution vector and are not https',
            ],
            'plain http' => [
                UserSocialLinkPlatform::Twitch->value, 'http://twitch.tv/someone', false,
                'Only https is accepted, even for an otherwise allowed host',
            ],
            'protocol relative' => [
                UserSocialLinkPlatform::Twitch->value, '//twitch.tv/someone', false,
                'Without an explicit https scheme the URL is rejected',
            ],

            // Host rejections - the suffix-matching traps
            'host merely ending in the allowed name' => [
                UserSocialLinkPlatform::Twitch->value, 'https://nottwitch.tv/someone', false,
                'nottwitch.tv must not pass as twitch.tv - matching is on a dot-prefixed suffix',
            ],
            'allowed host as a subdomain of an attacker domain' => [
                UserSocialLinkPlatform::Twitch->value, 'https://twitch.tv.evil.com/someone', false,
                'The allowed host appearing as a prefix of another domain must not pass',
            ],
            'wrong platform for the host' => [
                UserSocialLinkPlatform::Twitch->value, 'https://youtube.com/@someone', false,
                'A YouTube link submitted under the Twitch field is rejected',
            ],

            // Malformed / unknown
            'unknown platform' => [
                null, 'https://myspace.com/someone', false,
                'A platform outside the enum is rejected regardless of the URL',
            ],
            'not a url at all' => [
                UserSocialLinkPlatform::Twitch->value, 'not a url', false,
                'Input without a scheme and host cannot be validated and is rejected',
            ],
            'empty string' => [
                UserSocialLinkPlatform::Twitch->value, '', false,
                'An empty string has no scheme or host',
            ],
        ];
    }

    #[Test]
    public function getIconClass_givenEveryKnownPlatform_returnsANonEmptyClass(): void
    {
        // Arrange & Act & Assert
        foreach (UserSocialLinkPlatform::cases() as $platform) {
            $socialLink           = new UserSocialLink();
            $socialLink->platform = $platform->value;

            $this->assertNotEmpty(
                $socialLink->getIconClass(),
                sprintf('Platform %s must map to an icon class', $platform->value),
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
            UserSocialLinkPlatform::Website->icon(),
            $result,
            'An unknown platform must not render a broken icon',
        );
    }

    #[Test]
    public function hostsAndIcon_givenEveryPlatformCase_resolveWithoutError(): void
    {
        // Act & Assert
        foreach (UserSocialLinkPlatform::cases() as $platform) {
            $this->assertNotEmpty(
                $platform->icon(),
                sprintf('Platform %s is missing an icon', $platform->value),
            );

            // Website is the only platform allowed to accept any host (a null hosts() list)
            if ($platform === UserSocialLinkPlatform::Website) {
                $this->assertNull($platform->hosts());
            } else {
                $this->assertNotEmpty(
                    $platform->hosts(),
                    sprintf('Platform %s is missing a host allowlist', $platform->value),
                );
            }
        }
    }
}
