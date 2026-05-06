<?php

namespace App\Policies;

use App\Models\Dungeon;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DungeonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the dungeon.
     */
    public function view(?User $user, Dungeon $dungeon): bool
    {
        return $dungeon->active && $dungeon->expansion->active;
    }
}
