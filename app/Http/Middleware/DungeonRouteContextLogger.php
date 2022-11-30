<?php

namespace App\Http\Middleware;

use App\Models\DungeonRoute;
use Closure;

class DungeonRouteContextLogger
{
    public function handle($request, Closure $next)
    {
        $dungeonRoute = $request->route('dungeonroute');

        if ($dungeonRoute instanceof DungeonRoute) {
            \Log::withContext([
                'publicKey'        => $dungeonRoute->public_key,
                'mappingVersionId' => $dungeonRoute->mapping_version_id,
            ]);
        }

        return $next($request);
    }
}
