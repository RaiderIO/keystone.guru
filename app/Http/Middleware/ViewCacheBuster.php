<?php namespace App\Http\Middleware;

class ViewCacheBuster
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // Clear view cache in development (only) with every request. This is highly annoying otherwise with views not refreshing
        // https://stackoverflow.com/questions/20579182/laravel-and-view-caching-in-development-cant-see-changes-right-away
        if (config('app.debug')) {
            $cachedViewsDirectory = app('path.storage') . '/views/';
            $files                = glob($cachedViewsDirectory . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        return $next($request);
    }
}
