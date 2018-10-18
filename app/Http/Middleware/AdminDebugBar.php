<?php namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;

/**
 * https://stackoverflow.com/questions/32584700/how-to-prevent-laravel-routes-from-being-accessed-directly-i-e-non-ajax-reques
 */
class AdminDebugBar
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            \Debugbar::enable();
        } else {
            \Debugbar::disable();
        }

        return $next($request);
    }
}