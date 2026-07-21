<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddsTraceIdToContext
{
    /**
     * Tags the request with a unique trace_id through Laravel's Context: every log line written during this request
     * (and inside any queued job dispatched from it, Context is dehydrated into the job payload) carries the trace_id,
     * so a single grep over the logs reconstructs the full narrative of a request across services and jobs.
     */
    public function handle(Request $request, Closure $next): Response
    {
        Context::add('trace_id', (string)Str::uuid());

        return $next($request);
    }
}
