<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Clears the map facade style override at the start of every request.
 *
 * User::forceMapFacadeStyle() writes a *static* property, which under Octane/Swoole is worker state
 * that lives until the worker is recycled - not request state. The three controllers that render an
 * embedded route, dungeon explore or heatmap map set it from a query parameter and never clear it,
 * so without this every later request handled by that same worker inherits the map facade style of
 * whoever hit that page. That is what let a kill zone save decide it was not looking at a facade map
 * while the client had placed its location on one, persisting a facade floor_id (#3917).
 */
class ResetsMapFacadeStyleOverride
{
    public function handle(Request $request, Closure $next): Response
    {
        User::forceMapFacadeStyle(null);

        return $next($request);
    }
}
