<?php

namespace App\Policies;

use App\Models\Expansion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ExpansionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the expansion.
     *
     * A retired expansion is gone for everyone, not withheld from this particular user - deny
     * with a 404 rather than the generic 403 so the response reflects that.
     */
    public function view(?User $user, Expansion $expansion): Response
    {
        if (!$expansion->active) {
            return $this->denyAsNotFound(__('policy.expansion_not_active'));
        }

        return $this->allow();
    }
}
