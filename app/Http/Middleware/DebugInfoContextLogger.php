<?php

namespace App\Http\Middleware;

use App\Models\DungeonRoute;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Log;
use Ramsey\Uuid\Uuid;

class DebugInfoContextLogger
{
    public function handle(Request $request, Closure $next)
    {
        $dungeonRoute = $request->route('dungeonroute');

        $context = [
            'url'           => $request->url(),
            'method'        => $request->method(),
            'correlationId' => correlationId(),
        ];

        if ($dungeonRoute instanceof DungeonRoute) {
            $context = array_merge($context, [
                'publicKey'        => $dungeonRoute->public_key,
                'mappingVersionId' => $dungeonRoute->mapping_version_id,
            ]);
        }

        Log::withContext($context);

        /** @var Response $response */
        $response = $next($request);
//        $response->header('X-Correlation-Id', $context['correlationId']);

        return $response;
    }
}
