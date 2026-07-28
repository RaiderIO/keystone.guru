<?php

namespace App\Policies;

use App\Models\GameVersion\GameVersion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class GameVersionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the game version.
     *
     * A retired game version is gone for everyone, not withheld from this particular user - deny
     * with a 404 rather than the generic 403 so the response reflects that.
     */
    public function view(?User $user, GameVersion $gameVersion): Response
    {
        if (!$gameVersion->active) {
            return $this->denyAsNotFound(__('policy.game_version_not_active'));
        }

        return $this->allow();
    }
}
