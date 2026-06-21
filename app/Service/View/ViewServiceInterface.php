<?php

namespace App\Service\View;

use App\Models\GameServerRegion;

interface ViewServiceInterface
{
    /**
     * @param bool $useCache True to use the cache, false to regenerate it.
     */
    /**

     * @return array<int, mixed>
     */

    /**


     * @return array<int, mixed>
     */

    public function getGlobalViewVariables(bool $useCache = true): array;

    /**
     * @param bool $useCache True to use the cache, false to regenerate it.
     */
    /**

     * @return array<int, mixed>
     */

    /**


     * @return array<int, mixed>
     */

    public function getGameServerRegionViewVariables(GameServerRegion $gameServerRegion, bool $useCache = true): array;

    public function shouldLoadViewVariables(string $pathInfo): bool;
}
