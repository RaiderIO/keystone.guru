<?php namespace App\Http\Middleware;

use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Teapot\StatusCode\Http;

class ReadOnlyMode
{
    private ReadOnlyModeServiceInterface $readOnlyModeService;

    /**
     * @param ReadOnlyModeServiceInterface $readOnlyModeService
     */
    public function __construct(ReadOnlyModeServiceInterface $readOnlyModeService)
    {
        $this->readOnlyModeService = $readOnlyModeService;
    }


    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->method() !== 'GET' && $this->readOnlyModeService->isReadOnly()) {
            return response('Service Unavailable - site is in read-only mode', Http::SERVICE_UNAVAILABLE);
        }
        return $next($request);
    }
}
