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
    public const string ROLE_ADMIN         = 'admin';

    public const array ROLE_ALL = [
        self::ROLE_USER,
        self::ROLE_INTERNAL_TEAM,
        self::ROLE_ADMIN,
    ];

    public static function roles(array $roles): string
    {
        return implode('|', $roles);
    }
}
