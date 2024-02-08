<?php

namespace App\Http\Middleware;

use App\Models\DungeonRoute\DungeonRoute;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Log;

class DebugInfoContextLogger
{
    public function handle(Request $request, Closure $next)
    {
        $dungeonRoute = $request->route('dungeonroute') ?? $request->route('dungeonRoute');

        $context = [
            'correlationId' => correlationId(),
        ];

        if ($dungeonRoute instanceof DungeonRoute) {
            $context = array_merge($context, [
                'publicKey'        => $dungeonRoute->public_key,
                'mappingVersionId' => $dungeonRoute->mapping_version_id,
            ]);
        }

        Log::withContext($context);

        // @TODO use structured logging?
        logger()->debug('DebugInfoContextLogger::handle', [
            'url'           => $request->url(),
            'method'        => $request->method(),
        ]);

        /** @var Response $response */
        $response = $next($request);
        $response->header('X-Correlation-Id', $context['correlationId']);

        return $response;
    }
}
