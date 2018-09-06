<?php

namespace App\Http\Controllers\Traits;

use App\Models\DungeonRoute;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;

trait PublicKeyDungeonRoute
{
    function _getDungeonRouteFromPublicKey($publicKey)
    {
        $dungeonRoute = DungeonRoute::where('public_key', '=', $publicKey)->firstOrFail();

        // @TODO handle this in a policy?
        $user = Auth::user();
        if ($user !== null && $dungeonRoute->author_id !== $user->id) {
            throw new Exception('Unauthorized');
        }

        return $dungeonRoute;
    }
}