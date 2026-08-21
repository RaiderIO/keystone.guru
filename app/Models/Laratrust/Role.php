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
    /**
     * An AI agent's account (#4227): everything internal_team may do, plus the read-only agent-facing API endpoints
     * that are otherwise admin-only - and nothing else of what admin can do (no admin panel, no write endpoints).
     */
    public const string ROLE_AI_AGENT = 'ai_agent';
    public const string ROLE_ADMIN    = 'admin';

    public const array ROLE_ALL = [
        self::ROLE_USER,
        self::ROLE_INTERNAL_TEAM,
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

    /** @param array<int, string> $roles */
    public static function roles(array $roles): string
    {
        return implode('|', $roles);
    }
}
