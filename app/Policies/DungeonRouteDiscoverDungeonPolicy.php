<?php

namespace App\Policies;

use App\Models\Dungeon;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DungeonRouteDiscoverDungeonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the dungeon.
     *
     * @param User|null $user
     * @param Dungeon $dungeon
     * @return mixed
     */
    public function view(?User $user, Dungeon $dungeon)
    {
        return $dungeon->active && $dungeon->expansion->active;
    }
}
