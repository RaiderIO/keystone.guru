<?php

namespace App\Service\GameVersion;

use App\Models\GameVersion\GameVersion;
use App\Models\User;
use App\Service\Cookies\CookieServiceInterface;

class GameVersionService implements GameVersionServiceInterface
{
    private const GAME_VERSION_COOKIE = 'game_version';

    public function __construct(
        private readonly CookieServiceInterface $cookieService
    ) {
    }

    public function setGameVersion(GameVersion $gameVersion, ?User $user): void
    {
        $user?->update(['game_version_id' => $gameVersion->id]);

        // Unit tests and artisan commands don't like this
        // Nor do we want to keep setting the cookie if it hasn't changed
        if (!app()->runningInConsole() && ($_COOKIE[self::GAME_VERSION_COOKIE] ?? null) !== $gameVersion->key) {
            // Set the new cookie
            $this->cookieService->setCookie(self::GAME_VERSION_COOKIE, $gameVersion->key);
        }
    }

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
