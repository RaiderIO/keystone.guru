<?php

namespace App\Models\Laratrust;

use Eloquent;
use Laratrust\Models\Role as LaratrustRole;

/**
 * @mixin Eloquent
 */
class Role extends LaratrustRole
{
    protected $hidden = []; // <-- unhide 'pivot'

    public const string ROLE_USER          = 'user';
    public const string ROLE_INTERNAL_TEAM = 'internal_team';
    public const string ROLE_BETA_TESTER   = 'beta_tester';
    public const string ROLE_AI_AGENT      = 'ai_agent';
    public const string ROLE_ADMIN         = 'admin';

    public const array ROLE_ALL = [
        self::ROLE_USER,
        self::ROLE_INTERNAL_TEAM,
        self::ROLE_BETA_TESTER,
        self::ROLE_AI_AGENT,
        self::ROLE_ADMIN,
    ];

    /**
     * The roles that are "one of us" - they get the internal-team extras (rate limit exemptions, internal features,
     * internal API documentation). Admin is included because an admin can do everything internal team can.
     */
    public const array ROLES_INTERNAL = [
        self::ROLE_ADMIN,
        self::ROLE_INTERNAL_TEAM,
        self::ROLE_AI_AGENT,
    ];

    /**
     * The roles that get early access - internal-only features and the web rate limit exemption. Unlike
     * {@see self::ROLES_INTERNAL} this includes beta testers, who are not Raider.IO staff and so get no internal API
     * documentation, internal API data or staff badge.
     */
    public const array ROLES_EARLY_ACCESS = [
        ...self::ROLES_INTERNAL,
        self::ROLE_BETA_TESTER,
    ];

    /** @param array<int, string> $roles */
    public static function roles(array $roles): string
    {
        return implode('|', $roles);
    }
}
