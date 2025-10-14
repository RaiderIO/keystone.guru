<?php

namespace App\Policies;

use App\Models\GameVersion\GameVersion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DungeonRouteDiscoverGameVersionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the game version.
     */
    public function view(?User $user, GameVersion $gameVersion): bool
    {
        return $gameVersion->active;
    }
}
