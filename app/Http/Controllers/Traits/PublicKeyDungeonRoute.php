<?php

namespace App\Http\Controllers\Traits;

use App\Models\DungeonRoute;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;

trait PublicKeyDungeonRoute
{
    /**
     * @param string $publicKey
     * @param bool $auth
     * @return DungeonRoute
     * @throws Exception
     */
    function _getDungeonRouteFromPublicKey(string $publicKey, bool $auth = true): DungeonRoute
    {
        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::where('public_key', $publicKey)->firstOrFail();

        if ($auth) {
            // @TODO handle this in a policy?
            // Author may be -1 to indicate a route that's in try mode, don't auth those
            if ($dungeonRoute->author_id !== -1) {
                // Otherwise, must be logged in and be the author of said route
                if (!Auth::check() || $dungeonRoute->author_id !== Auth::user()->id) {
                    throw new Exception('Unauthorized');
                }
            }
        }

        return $dungeonRoute;
    }
}
