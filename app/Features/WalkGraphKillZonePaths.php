<?php

namespace App\Features;

use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;

/**
 * Draws the line between a route's pulls along the walk graph: how players actually walk to the next pull, instead
 * of a straight line through walls. Pulls the walk graph cannot connect keep their straight line.
 *
 * While the walked line is being judged on real routes this resolves only for admins, the internal team and beta
 * testers, and only once an admin has switched it on.
 */
class WalkGraphKillZonePaths
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
        return $user?->hasRole(Role::ROLES_EARLY_ACCESS) ?? false;
    }
}
