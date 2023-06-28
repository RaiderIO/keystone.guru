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
            'correlationId' => Uuid::uuid4()->toString(),
        ];

        if ($dungeonRoute instanceof DungeonRoute) {
            $context = array_merge($context, [
                'publicKey'        => $dungeonRoute->public_key,
                'mappingVersionId' => $dungeonRoute->mapping_version_id,
            ]);
        }

        // @TODO Re-enable this
        Log::withContext($context);

        /** @var Response $response */
        $response = $next($request);
        $response->header('X-Correlation-Id', $context['correlationId']);



        return $response;
    }
}
