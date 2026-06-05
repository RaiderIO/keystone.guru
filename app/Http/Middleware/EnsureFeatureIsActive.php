<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureIsActive
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (! Feature::active($feature)) {
            abort(404);
        }

        return $next($request);
    }
}
