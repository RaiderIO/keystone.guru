<?php

namespace App\Policies;

use App\Models\Laratrust\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the target user's profile settings.
     */
    public function update(User $user, User $target): bool
    {
        // A user may only ever update their own settings
        return $user->id === $target->id;
    }

    /**
     * Determine whether the user can delete the target user's account.
     * Admins cannot delete themselves - the site would be left without one.
     */
    public function delete(User $user, User $target): Response
    {
        if ($user->id !== $target->id) {
            return $this->deny();
        }

        if ($user->hasRole(Role::ROLE_ADMIN)) {
            return $this->deny(__('controller.profile.flash.admins_cannot_delete_themselves'));
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can grant or revoke the given role on the target user.
     * Granting or revoking admin is restricted to the super admins configured in
     * keystoneguru.super_admins; every other role only requires being an admin.
     */
    public function makeRole(User $user, User $target, string $role): Response
    {
        if (!$user->hasRole(Role::ROLE_ADMIN)) {
            return $this->deny();
        }

        if ($role !== Role::ROLE_ADMIN) {
            return $this->allow();
        }

        return in_array($user->name, config('keystoneguru.super_admins', []), true) ?
            $this->allow() :
            $this->deny(__('policy.make_role_only_super_admins_may_grant_admin'));
    }

    /**
     * Determine whether the user can revoke the ad-free giveaway the target user currently has.
     * Only the giver may take their own giveaway back. Callers already guard the "no giveaway to
     * revoke" case themselves before authorizing, so this only needs to carry the "not yours"
     * message the old inline checks showed instead of a generic deny.
     */
    public function revokeAdFreeGiveaway(User $user, User $target): Response
    {
        return $target->patreonAdFreeGiveaway !== null &&
            $target->patreonAdFreeGiveaway->giver_user_id === $user->id ?
            $this->allow() :
            $this->deny(__('controller.profile.error.remove_ad_free_giveaway_not_yours'));
    }
}
