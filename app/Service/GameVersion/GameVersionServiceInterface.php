<?php

namespace App\Service\GameVersion;

use App\Models\GameVersion\GameVersion;
use App\Models\User;

interface GameVersionServiceInterface
{
    public function setGameVersion(GameVersion $gameVersion, ?User $user): void;

    public function getGameVersion(?User $user): GameVersion;
}
