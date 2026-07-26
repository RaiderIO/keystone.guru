<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Metric\APIDungeonRouteMetricFormRequest;
use App\Http\Requests\Metric\APIMetricFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\Metric\MetricServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class AjaxMetricController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function store(APIMetricFormRequest $request, MetricServiceInterface $metricService): Response
    {
        $validated = $request->validated();

        // A DungeonRoute reported through the generic endpoint must still respect the route's own
        // view gate, the same as storeDungeonRoute() - otherwise it's a blanket bypass of that gate.
        if ($validated['model_class'] === DungeonRoute::class && $validated['model_id'] !== null) {
            $dungeonRoute = DungeonRoute::find($validated['model_id']);

            if ($dungeonRoute !== null) {
                Gate::authorize('view', $dungeonRoute);
            }
        }

        $metricService->storeMetric($request['model_id'], $request['model_class'], $validated['category'], $validated['tag'], $validated['value']);

        return response()->noContent();
    }

    /**
     * @throws AuthorizationException
     */
    public function storeDungeonRoute(
        APIDungeonRouteMetricFormRequest $request,
        DungeonRoute                     $dungeonRoute,
        MetricServiceInterface           $metricService,
    ): Response {
        Gate::authorize('view', $dungeonRoute);

        $validated = $request->validated();

        $metricService->storeMetricByModel($dungeonRoute, $validated['category'], $validated['tag'], $validated['value']);

        return response()->noContent();
    }
}
