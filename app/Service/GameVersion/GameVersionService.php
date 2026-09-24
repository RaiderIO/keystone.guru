<?php

namespace App\Service\GameVersion;

use App\Models\GameVersion\GameVersion;
use App\Models\User;
use App\Service\Cookies\CookieServiceInterface;
use App\Service\View\ViewServiceInterface;

class GameVersionService implements GameVersionServiceInterface
{
    private const string GAME_VERSION_COOKIE = 'game_version';

    public function __construct(
        private readonly CookieServiceInterface $cookieService,
        private readonly ViewServiceInterface   $viewService,
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
            $gameVersionId = GameVersion::ALL[$_COOKIE[self::GAME_VERSION_COOKIE]] ?? 0;

            // Every returning guest carries this cookie and the header asks on every page, so answer from
            // the cached list of active game versions before asking the database.
            $gameVersion = $gameVersionId === 0 ? null :
                ($this->viewService->getAllGameVersions()->firstWhere('id', $gameVersionId) ?? GameVersion::find($gameVersionId));
        }

        if ($gameVersion === null) {
            $gameVersion = ($user === null ? null : GameVersion::getUserGameVersion($user)) ?? GameVersion::getDefaultGameVersion();

            $this->setGameVersion($gameVersion, $user);
        }

        return $gameVersion;
    }
}
