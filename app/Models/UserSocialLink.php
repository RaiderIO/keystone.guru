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
 * must be https, and the host must appear in the matching {@see UserSocialLinkPlatform::hosts()}.
 * That rules out `javascript:` payloads and stops the profile from being used to launder arbitrary
 * outbound links.
 *
 * `platform` is deliberately kept a plain string column rather than a native enum cast - an
 * unrecognised value (e.g. a platform removed from {@see UserSocialLinkPlatform} after rows using it
 * were already persisted) must still degrade gracefully via {@see self::getIconClass()} rather than
 * throw on hydration.
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
     *
     * Falls back to the website icon for a platform value that no longer maps to a
     * {@see UserSocialLinkPlatform} case, rather than throwing.
     */
    public function getIconClass(): string
    {
        return (UserSocialLinkPlatform::tryFrom($this->platform) ?? UserSocialLinkPlatform::Website)->icon();
    }

    /**
     * Whether the given URL is acceptable for the given platform.
     *
     * Enforces https and, for everything but the free-form website link, an allow-listed host.
     */
    public static function isValidUrlForPlatform(string $platform, string $url): bool
    {
        return UserSocialLinkPlatform::tryFrom($platform)?->isValidUrl($url) ?? false;
    }
}
