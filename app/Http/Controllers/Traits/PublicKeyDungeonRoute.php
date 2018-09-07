<?php

namespace App\Http\Controllers\Traits;

use App\Models\DungeonRoute;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;

trait PublicKeyDungeonRoute
{
    /**
     * @param $publicKey
     * @return DungeonRoute
     */
    function _getDungeonRouteFromPublicKey($publicKey)
    {
        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::where('public_key', '=', $publicKey)->firstOrFail();

        // @TODO handle this in a policy?
        $user = Auth::user();
        if ($user !== null && $dungeonRoute->author_id !== $user->id) {
            throw new Exception('Unauthorized');
        }

        return $dungeonRoute;
    }
}