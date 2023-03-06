<?php

namespace App\Http\Middleware;

use App\Models\DungeonRoute;
use Closure;
use Request;

class DungeonRouteContextLogger
{
    public function handle(Request $request, Closure $next)
    {
        $dungeonRoute = $request->route('dungeonroute');

        $context = [
            'url'    => $request->url(),
            'method' => $request->method(),
            'params' => $request->all(),
        ];

        if ($dungeonRoute instanceof DungeonRoute) {
            $context = array_merge($context, [
                'publicKey'        => $dungeonRoute->public_key,
                'mappingVersionId' => $dungeonRoute->mapping_version_id,
            ]);
        }

        \Log::withContext($context);

        return $next($request);
    }
}
