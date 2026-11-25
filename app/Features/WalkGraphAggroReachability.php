<?php

namespace App\Features;

use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;

/**
 * Makes the MCP server's pack neighbours aggro-aware: a pack only counts as reachable from another when the walk
 * graph connects them without walking into the aggro radius of a third enemy or patrol, and a long way around is
 * flagged as a detour. It also lists the packs each dungeon start can open with, and the packs a new pull can be
 * followed up with.
 *
 * While the reachability is being judged on real routes this resolves only for admins, the internal team and beta
 * testers, and only once an admin has switched it on.
 */
class WalkGraphAggroReachability
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
