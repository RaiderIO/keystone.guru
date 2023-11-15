<?php


namespace App\Service\View;

use App\Models\GameServerRegion;

interface ViewServiceInterface
{
    /**
     * @param bool $useCache True to use the cache, false to regenerate it.
     * @return array
     */
    public function getGlobalViewVariables(bool $useCache = true): array;

    /**
     * @param GameServerRegion $gameServerRegion
     * @param bool             $useCache True to use the cache, false to regenerate it.
     * @return array
     */
    public function getGameServerRegionViewVariables(GameServerRegion $gameServerRegion, bool $useCache = true): array;
}
