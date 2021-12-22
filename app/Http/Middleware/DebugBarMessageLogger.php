<?php namespace App\Http\Middleware;

use App\Logic\Utils\Counter;
use Closure;
use Debugbar;
use Illuminate\Http\Request;

class DebugBarMessageLogger
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (config('app.debug')) {
            // Dev dependency
            if (class_exists('Debugbar')) {
                Debugbar::info('CacheService details');
                foreach (Counter::getAll() as $counter) {
                    Debugbar::info('- ' . $counter);
                }
            }
        }

        return $response;
    }
}
