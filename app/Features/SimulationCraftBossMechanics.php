<?php

namespace App\Features;

use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;

/**
 * Applies the boss mechanics that `combatlog:buildencountermechanics` derives from combat logs to the
 * SimulationCraft export: adds, invulnerability windows and forced movement as child raid events of the boss pull,
 * shared health, friendly NPCs left out and affix-exempt NPCs scaled by the key level only.
 *
 * While the simulated results are being judged on real routes this resolves only for admins, the internal team and
 * beta testers, and only once an admin has switched it on.
 */
class SimulationCraftBossMechanics
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
