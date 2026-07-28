<?php

namespace App\Policies;

use App\Models\Dungeon;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class DungeonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the dungeon.
     *
     * A retired dungeon (or one whose expansion was retired) is gone for everyone, not withheld
     * from this particular user - deny with a 404 rather than the generic 403 so the response
     * reflects that.
     */
    public function view(?User $user, Dungeon $dungeon): Response
    {
        if (!$dungeon->active || !$dungeon->expansion->active) {
            return $this->denyAsNotFound(__('policy.dungeon_not_active'));
        }

        return $this->allow();
    }
}
