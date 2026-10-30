<?php

namespace App\Features;

use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;

/**
 * Gates the notification system: the unread badge, the notification feed and the admin page that
 * sends a custom notification.
 *
 * While in development this resolves only for admins, the internal team and beta testers, so the
 * work can merge dark. Opening it to the public is a one-line change here - drop the role check and
 * return true once {@see Feature::getAdminValue()} passes.
 */
class Notifications
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
