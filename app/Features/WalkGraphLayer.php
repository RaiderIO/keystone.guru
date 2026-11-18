<?php

namespace App\Features;

use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;
use Laravel\Pennant\Feature as Pennant;

/**
 * The map layer that draws the walk graph: where players walked, built from combat logs.
 *
 * While the layer is being checked against real graphs this resolves only for admins, and only once an admin has
 * switched it on.
 */
class WalkGraphLayer
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
        return $user?->hasRole(Role::ROLE_ADMIN) ?? false;
    }

    /**
     * Whether a map offers the walk graph layer: the page opted in and the feature is active for the current user.
     */
    public static function isEnabledForMap(bool $pageAllowsWalkGraph): bool
    {
        return $pageAllowsWalkGraph && Pennant::active(self::class);
    }
}
