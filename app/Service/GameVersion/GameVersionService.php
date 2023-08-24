<?php

namespace App\Service\GameVersion;

use App\Models\GameVersion\GameVersion;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\User;
use Auth;

class GameVersionService implements GameVersionServiceInterface
{
    private const GAME_VERSION_COOKIE = 'game_version';


    /**
     * @inheritDoc
     */
    public function setGameVersion(GameVersion $gameVersion, ?User $user): void
    {
        optional($user)->update(['game_version_id' => $gameVersion->id]);

        $_COOKIE[self::GAME_VERSION_COOKIE] = $gameVersion->key;
    }


    /**
     * @inheritDoc
     */
    public function getGameVersion(?User $user): GameVersion
    {
        $gameVersion = null;
        if ($user === null && isset($_COOKIE[self::GAME_VERSION_COOKIE])) {
            $gameVersion = GameVersion::find(GameVersion::ALL[self::GAME_VERSION_COOKIE] ?? 0);
        }

        if ($gameVersion === null) {
            $gameVersion = GameVersion::getUserOrDefaultGameVersion();
        }

        return $gameVersion;
    }
}
