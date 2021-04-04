<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Url\Url;

class RedesignRedirect
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $redesign = (int)($_COOKIE['redesign'] ?? 0);
        $isRedesign = str_contains(env('APP_URL'), 'redesign.');
        // Redirect to the redesign site
        if ($redesign === 1 && !$isRedesign) {
            $url = Url::fromString($request->fullUrl());
            $url = $url->withHost(sprintf('redesign.%s', $url->getHost()));

            return redirect($url);
        } else if ($redesign === 0 && $isRedesign) {
            $url = Url::fromString($request->fullUrl());
            $url = $url->withHost(str_replace('redesign.', '', $url->getHost()));

            return redirect($url);
        }

        return $next($request);
    }
}