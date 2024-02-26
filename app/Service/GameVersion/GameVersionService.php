<?php

namespace App\Service\GameVersion;

use App\Models\GameVersion\GameVersion;
use App\User;

class GameVersionService implements GameVersionServiceInterface
{
    private const GAME_VERSION_COOKIE = 'game_version';

    /**
     * {@inheritDoc}
     */
    public function setGameVersion(GameVersion $gameVersion, ?User $user): void
    {
        optional($user)->update(['game_version_id' => $gameVersion->id]);

        // Unit tests and artisan commands don't like this
        if (! app()->runningInConsole()) {
            // Set the new cookie
            $_COOKIE[self::GAME_VERSION_COOKIE] = $gameVersion->key;
            setcookie(self::GAME_VERSION_COOKIE, $gameVersion->key, ['expires' => 0, 'path' => '/', 'domain' => null, 'secure' => true, 'httponly' => false]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getGameVersion(?User $user): GameVersion
    {
        $gameVersion = null;
        if ($user === null && isset($_COOKIE[self::GAME_VERSION_COOKIE])) {
            $gameVersion = GameVersion::find(GameVersion::ALL[$_COOKIE[self::GAME_VERSION_COOKIE]] ?? 0);
        }

        if ($gameVersion === null) {
            $gameVersion = GameVersion::getUserOrDefaultGameVersion();

            $this->setGameVersion($gameVersion, $user);
        }

        return $gameVersion;
    }
}
