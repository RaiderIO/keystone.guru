<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single social/streaming link shown on a user's public creator profile.
 *
 * URLs are user supplied and rendered on a public page, so they are constrained twice: the scheme
 * must be https, and the host must appear in this platform's entry of {@see self::PLATFORM_HOSTS}.
 * That rules out `javascript:` payloads and stops the profile from being used to launder arbitrary
 * outbound links.
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $platform
 * @property string $url
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property User $user
 *
 * @mixin Eloquent
 */
class UserSocialLink extends Model
{
    public const string PLATFORM_TWITCH    = 'twitch';
    public const string PLATFORM_YOUTUBE   = 'youtube';
    public const string PLATFORM_X         = 'x';
    public const string PLATFORM_DISCORD   = 'discord';
    public const string PLATFORM_BLUESKY   = 'bluesky';
    public const string PLATFORM_TIKTOK    = 'tiktok';
    public const string PLATFORM_INSTAGRAM = 'instagram';
    public const string PLATFORM_PATREON   = 'patreon';
    public const string PLATFORM_WEBSITE   = 'website';

    /**
     * Rendered in this order on the public profile.
     *
     * @var array<int, string>
     */
    public const array ALL = [
        self::PLATFORM_TWITCH,
        self::PLATFORM_YOUTUBE,
        self::PLATFORM_X,
        self::PLATFORM_DISCORD,
        self::PLATFORM_BLUESKY,
        self::PLATFORM_TIKTOK,
        self::PLATFORM_INSTAGRAM,
        self::PLATFORM_PATREON,
        self::PLATFORM_WEBSITE,
    ];

    /**
     * Hosts accepted per platform. A `null` entry means any host is allowed, which is only the case
     * for the free-form website link.
     *
     * Matching is exact or on a `.`-prefixed suffix, so `twitch.tv` accepts `www.twitch.tv` but
     * rejects `nottwitch.tv` and `twitch.tv.evil.com`.
     *
     * @var array<string, array<int, string>|null>
     */
    public const array PLATFORM_HOSTS = [
        self::PLATFORM_TWITCH    => ['twitch.tv'],
        self::PLATFORM_YOUTUBE   => ['youtube.com', 'youtu.be'],
        self::PLATFORM_X         => ['x.com', 'twitter.com'],
        self::PLATFORM_DISCORD   => ['discord.gg', 'discord.com'],
        self::PLATFORM_BLUESKY   => ['bsky.app'],
        self::PLATFORM_TIKTOK    => ['tiktok.com'],
        self::PLATFORM_INSTAGRAM => ['instagram.com'],
        self::PLATFORM_PATREON   => ['patreon.com'],
        self::PLATFORM_WEBSITE   => null,
    ];

    /**
     * Font Awesome classes, all verified present in the pinned Font Awesome 7 release.
     *
     * @var array<string, string>
     */
    public const array PLATFORM_ICONS = [
        self::PLATFORM_TWITCH    => 'fab fa-twitch',
        self::PLATFORM_YOUTUBE   => 'fab fa-youtube',
        self::PLATFORM_X         => 'fab fa-x-twitter',
        self::PLATFORM_DISCORD   => 'fab fa-discord',
        self::PLATFORM_BLUESKY   => 'fab fa-bluesky',
        self::PLATFORM_TIKTOK    => 'fab fa-tiktok',
        self::PLATFORM_INSTAGRAM => 'fab fa-instagram',
        self::PLATFORM_PATREON   => 'fab fa-patreon',
        self::PLATFORM_WEBSITE   => 'fas fa-globe',
    ];

    protected $fillable = [
        'user_id',
        'platform',
        'url',
        'updated_at',
        'created_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The Font Awesome icon class for this link's platform.
     */
    public function getIconClass(): string
    {
        return self::PLATFORM_ICONS[$this->platform] ?? self::PLATFORM_ICONS[self::PLATFORM_WEBSITE];
    }

    /**
     * Whether the given URL is acceptable for the given platform.
     *
     * Enforces https and, for everything but the free-form website link, an allow-listed host.
     */
    public static function isValidUrlForPlatform(string $platform, string $url): bool
    {
        if (!array_key_exists($platform, self::PLATFORM_HOSTS)) {
            return false;
        }

        $parsed = parse_url($url);

        // parse_url returns false on seriously malformed input
        if ($parsed === false || !isset($parsed['scheme'], $parsed['host'])) {
            return false;
        }

        // Only https, which also excludes javascript: and data: payloads
        if (strtolower((string)$parsed['scheme']) !== 'https') {
            return false;
        }

        $allowedHosts = self::PLATFORM_HOSTS[$platform];

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
