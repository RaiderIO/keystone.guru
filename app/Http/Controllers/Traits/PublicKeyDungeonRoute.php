<?php

namespace App\Http\Controllers\Traits;

use App\Models\DungeonRoute;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;

trait PublicKeyDungeonRoute
{
    /**
     * @param $publicKey
     * @param $auth
     * @return DungeonRoute
     * @throws Exception
     */
    function _getDungeonRouteFromPublicKey($publicKey, $auth = true)
    {
        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::where('public_key', '=', $publicKey)->firstOrFail();

        if ($auth) {
            // @TODO handle this in a policy?
            if (!Auth::check() || $dungeonRoute->author_id !== Auth::user()->id) {
                throw new Exception('Unauthorized');
            }
        }

        return $dungeonRoute;
    }
}