<?php

namespace App\Features;

use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;

/**
 * Gates the creator podium: the revamped public profile (bio, socials, pinned routes), the creator
 * directory, and the featured-creators row on the discover landing page.
 *
 * While in development this resolves only for admins and the internal team, so the work can merge
 * dark. Opening it to the public is a one-line change here - drop the role check and return true
 * once {@see Feature::getAdminValue()} passes - rather than a code change spread across the views.
 */
class CreatorProfiles
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
        return $user?->hasRole([
            Role::ROLE_ADMIN,
            Role::ROLE_INTERNAL_TEAM,
        ]) ?? false;
    }
}
