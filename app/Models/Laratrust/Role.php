<?php

namespace App\Models\Laratrust;

use Eloquent;
use Laratrust\Models\Role as LaratrustRole;

/**
 * @mixin Eloquent
 */
class Role extends LaratrustRole
{
    //
    public const ROLE_USER          = 'user';
    public const ROLE_INTERNAL_TEAM = 'internal_team';
    public const ROLE_ADMIN         = 'admin';
}
