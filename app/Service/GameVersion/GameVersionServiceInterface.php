<?php

namespace App\Service\GameVersion;

use App\Models\GameVersion\GameVersion;
use App\User;

interface GameVersionServiceInterface
{
    /**
     * @param User|null $user
     * @return void
     */
    public function setGameVersion(GameVersion $gameVersion, ?User $user): void;

    /**
     * @param User|null $user
     *
     * @return GameVersion
     */
    public function getGameVersion(?User $user): GameVersion;
}
