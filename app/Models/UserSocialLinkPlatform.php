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
     * The profile link a username is appended to. `null` for the website, which takes a full link, and for Discord,
     * whose users have no profile link.
     */
    public function usernameUrlPrefix(): ?string
    {
        return match ($this) {
            self::Twitch                 => 'https://twitch.tv/',
            self::Youtube                => 'https://youtube.com/',
            self::X                      => 'https://x.com/',
            self::Bluesky                => 'https://bsky.app/profile/',
            self::Tiktok                 => 'https://tiktok.com/',
            self::Instagram              => 'https://instagram.com/',
            self::Patreon                => 'https://patreon.com/',
            self::Discord, self::Website => null,
        };
    }

    /**
     * Turns what a user entered - a username, an @handle or a full link - into the value to store, or `null` when it
     * is not valid for this platform.
     *
     * Every platform with a {@see self::usernameUrlPrefix()} stores that prefix plus the username. Discord stores
     * either an invite link or a bare username, the website any https link.
     */
    public function normalize(string $input): ?string
    {
        $input = trim($input);

        if ($input === '') {
            return null;
        }

        if ($this === self::Website) {
            $url = preg_match('/^[a-z][a-z0-9+.-]*:/i', $input) === 1 ? $input : sprintf('https://%s', $input);

            return filter_var($url, FILTER_VALIDATE_URL) !== false && $this->isValidUrl($url) ? $url : null;
        }

        $path = $this->extractPathFromLink($input);

        // Not a link to this platform, so what was entered is the username itself
        if ($path === null) {
            return $this->normalizeUsername($input);
        }

        if ($path === false) {
            return null;
        }

        if ($this === self::Discord) {
            return preg_match('#^(?:invite/)?([A-Za-z0-9-]{2,32})$#', $path, $matches) === 1
                ? sprintf('https://discord.gg/%s', $matches[1])
                : null;
        }

        // A link only names the profile through its path - a bare word there is a page, not a username
        return match ($this) {
            self::Youtube => preg_match('#^(@|channel/|c/|user/)#', $path) === 1 ? $this->normalizeUsername($path) : null,
            self::Bluesky => str_starts_with($path, 'profile/') ? $this->normalizeUsername(substr($path, strlen('profile/'))) : null,
            self::Tiktok  => str_starts_with($path, '@') ? $this->normalizeUsername($path) : null,
            default       => $this->normalizeUsername($path),
        };
    }

    /**
     * What the edit form shows for a stored value: the username for a platform with a {@see self::usernameUrlPrefix()},
     * the stored value itself otherwise.
     */
    public function toInputValue(string $storedValue): string
    {
        $prefix = $this->usernameUrlPrefix();
        if ($prefix === null) {
            return $storedValue;
        }

        $normalized = $this->normalize($storedValue) ?? $storedValue;

        return str_starts_with($normalized, $prefix) ? substr($normalized, strlen($prefix)) : $storedValue;
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

    /**
     * The path of a link to this platform, without slashes, query or fragment. `null` when the input is not a link at
     * all, `false` when it is a link to another site.
     */
    private function extractPathFromLink(string $input): string|false|null
    {
        $hostPattern = implode('|', array_map(static fn(string $host): string => preg_quote($host, '#'), $this->hosts() ?? []));

        // A link pasted without its scheme, e.g. twitch.tv/someone
        if (preg_match(sprintf('#^(?:[a-z0-9-]+\.)*(?:%s)(?:[/?\#]|$)#i', $hostPattern), $input) === 1) {
            $input = sprintf('https://%s', $input);
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $input) !== 1) {
            return null;
        }

        $parsed = parse_url($input);
        if ($parsed === false || !isset($parsed['scheme'], $parsed['host'])) {
            return false;
        }

        // http is upgraded, as the stored link is rebuilt on https anyway
        if (!in_array(strtolower($parsed['scheme']), ['http', 'https'], true) ||
            !$this->isValidUrl(sprintf('https://%s', $parsed['host']))) {
            return false;
        }

        return trim(rawurldecode($parsed['path'] ?? ''), '/');
    }

    /**
     * The value to store for a username of this platform, or `null` when it is not a valid username there.
     */
    private function normalizeUsername(string $username): ?string
    {
        $username = trim($username);
        $prefix   = $this->usernameUrlPrefix();

        $normalizedUsername = match ($this) {
            // Anything after the username is a sub-page of the profile (videos, about, ...)
            self::Twitch    => $this->matchUsername('#^@?([A-Za-z0-9_]{3,25})(?:/.*)?$#', $username),
            self::X         => $this->matchUsername('#^@?([A-Za-z0-9_]{1,15})(?:/.*)?$#', $username),
            self::Instagram => $this->matchUsername('#^@?([A-Za-z0-9_.]{1,30})(?:/.*)?$#', $username),
            self::Patreon   => $this->matchUsername('#^(?:c/)?@?([A-Za-z0-9_-]{1,64})(?:/.*)?$#', $username),
            self::Tiktok    => $this->matchUsername('#^@?([A-Za-z0-9_.]{2,24})(?:/.*)?$#', $username, '@%s'),
            self::Youtube   => $this->matchUsername('#^((?:channel|c|user)/[A-Za-z0-9_.-]{1,100})(?:/.*)?$#', $username)
                ?? $this->matchUsername('#^@?([A-Za-z0-9_.-]{3,30})(?:/.*)?$#', $username, '@%s'),
            // A handle is a domain, and anyone without one of their own has a bsky.social subdomain
            self::Bluesky => $this->matchUsername('#^@?(did:plc:[a-z2-7]{24}|[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)+)(?:/.*)?$#', $username)
                ?? $this->matchUsername('#^@?([A-Za-z0-9-]{1,63})$#', $username, '%s.bsky.social'),
            self::Discord => $this->matchUsername('#^@?([A-Za-z0-9_.]{2,32})$#', $username),
            self::Website => null,
        };

        if ($normalizedUsername === null) {
            return null;
        }

        // Bluesky handles and Discord usernames are case-insensitive and always shown in lowercase
        if (in_array($this, [self::Bluesky, self::Discord], true)) {
            $normalizedUsername = strtolower($normalizedUsername);
        }

        return $prefix === null ? $normalizedUsername : sprintf('%s%s', $prefix, $normalizedUsername);
    }

    private function matchUsername(string $pattern, string $username, string $format = '%s'): ?string
    {
        return preg_match($pattern, $username, $matches) === 1 ? sprintf($format, $matches[1]) : null;
    }
}
