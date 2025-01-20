<?php

namespace App\Http\Middleware\Api;

use App\Models\Metrics\Metric;
use App\Service\Metric\MetricServiceInterface;
use Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiMetrics
{
    public function __construct(private readonly MetricServiceInterface $metricService)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->runningUnitTests()) {
            $this->metricService->storeMetricByModel(
                Auth::user(),
                Metric::CATEGORY_API_CALL,
                $request->path(),
                1
            );
        }

        return $next($request);
    }
}
