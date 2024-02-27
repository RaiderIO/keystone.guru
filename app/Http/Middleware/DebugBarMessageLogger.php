<?php

namespace App\Http\Middleware;

use App\Logic\Utils\Counter;
use App\Logic\Utils\Stopwatch;
use Closure;
use Debugbar;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DebugBarMessageLogger
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (config('app.debug')) {
            // Dev dependency
            if (class_exists('Debugbar')) {
                Debugbar::info('Counter details');
                foreach (Counter::getAll() as $counter) {
                    Debugbar::info('- '.$counter);
                }

                Debugbar::info('Stopwatch details');
                foreach (Stopwatch::getAll() as $key => $time) {
                    Debugbar::info('- '.$key.' -> '.$time);
                }
            }
        }

        return $response;
    }
}
