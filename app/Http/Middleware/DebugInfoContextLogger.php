<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Logging\DebugInfoContextLoggerLoggingInterface;
use App\Models\DungeonRoute\DungeonRoute;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Log;

class DebugInfoContextLogger
{

    public function __construct(private readonly DebugInfoContextLoggerLoggingInterface $log)
    {
    }


    public function handle(Request $request, Closure $next): \Symfony\Component\HttpFoundation\Response
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

        try {
            $this->log->handleStart($request->url(), $request->method());

            /** @var Response $response */
            $response = $next($request);
            $response->header('X-Correlation-Id', $context['correlationId']);
        } finally {
            $this->log->handleEnd();
        }

        return $response;
    }
}
