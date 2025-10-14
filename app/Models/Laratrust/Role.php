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

    public const ROLE_USER          = 'user';
    public const ROLE_INTERNAL_TEAM = 'internal_team';
    public const ROLE_ADMIN         = 'admin';

    public const ROLE_ALL = [
        self::ROLE_USER,
        self::ROLE_INTERNAL_TEAM,
        self::ROLE_ADMIN,
    ];

    public static function roles(array $roles): string
    {
        return implode('|', $roles);
    }
}
