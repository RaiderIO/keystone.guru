<?php

namespace App\Features;

use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;

/**
 * Lets a user create and revoke OAuth personal access tokens for API v1 from their profile.
 *
 * While the OAuth rollout is being tried on production, this resolves only for admins, and only once an admin has
 * switched it on.
 */
class ApiTokens
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

        return $user?->hasRole(Role::ROLE_ADMIN) ?? false;
    }
}
