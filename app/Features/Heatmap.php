<?php

namespace App\Features;

use App\Models\Laratrust\Role;
use App\Models\User;

class Heatmap
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(User $user): bool
    {
        // Only if we have Opensearch setup and we have the correct roles!
        return !empty(config('opensearch-laravel.host')) && $user->hasRole([Role::ROLE_INTERNAL_TEAM, Role::ROLE_ADMIN]);
    }
}
