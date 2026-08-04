<?php

namespace App\Models;

/**
 * The social/streaming platforms a user may link on their public creator profile.
 *
 * Rendered in this order on the public profile.
 */
enum UserSocialLinkPlatform: string
{
    case Twitch    = 'twitch';
    case Youtube   = 'youtube';
    case X         = 'x';
    case Discord   = 'discord';
    case Bluesky   = 'bluesky';
    case Tiktok    = 'tiktok';
    case Instagram = 'instagram';
    case Patreon   = 'patreon';
    case Website   = 'website';

    /**
     * Hosts accepted for this platform. A `null` return means any host is allowed, which is only
     * the case for the free-form website link.
     *
     * Matching is exact or on a `.`-prefixed suffix, so `twitch.tv` accepts `www.twitch.tv` but
     * rejects `nottwitch.tv` and `twitch.tv.evil.com`.
     *
     * @return array<int, string>|null
     */
    public function hosts(): ?array
    {
        return match ($this) {
            self::Twitch    => ['twitch.tv'],
            self::Youtube   => ['youtube.com', 'youtu.be'],
            self::X         => ['x.com', 'twitter.com'],
            self::Discord   => ['discord.gg', 'discord.com'],
            self::Bluesky   => ['bsky.app'],
            self::Tiktok    => ['tiktok.com'],
            self::Instagram => ['instagram.com'],
            self::Patreon   => ['patreon.com'],
            self::Website   => null,
        };
    }

    /**
     * Font Awesome class, verified present in the pinned Font Awesome 7 release.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Twitch    => 'fab fa-twitch',
            self::Youtube   => 'fab fa-youtube',
            self::X         => 'fab fa-x-twitter',
            self::Discord   => 'fab fa-discord',
            self::Bluesky   => 'fab fa-bluesky',
            self::Tiktok    => 'fab fa-tiktok',
            self::Instagram => 'fab fa-instagram',
            self::Patreon   => 'fab fa-patreon',
            self::Website   => 'fas fa-globe',
        };
    }

    /**
     * Whether the given URL is acceptable for this platform.
     *
     * Enforces https and, for everything but the free-form website link, an allow-listed host.
     */
    public function isValidUrl(string $url): bool
    {
        $parsed = parse_url($url);

        // parse_url returns false on seriously malformed input
        if ($parsed === false || !isset($parsed['scheme'], $parsed['host'])) {
            return false;
        }

        // Only https, which also excludes javascript: and data: payloads
        if (strtolower((string)$parsed['scheme']) !== 'https') {
            return false;
        }

        $allowedHosts = $this->hosts();

        // The website link accepts any host, having already been constrained to https above
        if ($allowedHosts === null) {
            return true;
        }

        $host = strtolower((string)$parsed['host']);

        foreach ($allowedHosts as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, sprintf('.%s', $allowedHost))) {
                return true;
            }
        }

        return false;
    }
}
