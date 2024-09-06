<?php

namespace App\Models\Traits;

use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use Auth;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait SeederModel
{
    public static function boot(): void
    {
        parent::boot();

        // This model may NOT be deleted, it's read only! But if you're an admin, sure you can delete everything.
        static::deleting(function (Model $model) {
            /** @var User|null $user */
            $user = Auth::getUser();

            return $user?->hasRole(Role::ROLE_ADMIN) || $model instanceof MappingVersion || $model instanceof Floor || $model instanceof Enemy;
        });
    }
}
