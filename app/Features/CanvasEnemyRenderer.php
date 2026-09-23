<?php

namespace App\Features;

use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;

/**
 * Draws the enemies of read-only maps (explore, route view) on one shared canvas instead of one DOM
 * marker per enemy. Pages that can edit enemies always keep DOM markers, whatever this resolves to.
 *
 * While the renderer does not yet draw everything a DOM enemy shows, this resolves only for admins and
 * the internal team, and only once an admin has switched it on.
 */
class CanvasEnemyRenderer
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(?User $user): bool
    {
        // If the admin can't do it, we have disabled it entirely. So you can't do it either
        if (!Feature::getAdminValue(self::class)) {
            return false;
        }

        // Ok, feature is enabled, now check if YOU can do it
        return $user?->hasRole(Role::ROLES_INTERNAL) ?? false;
    }
}
