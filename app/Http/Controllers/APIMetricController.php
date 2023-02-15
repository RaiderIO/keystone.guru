<?php /** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Http\Controllers;

use App\Http\Requests\Metric\APIDungeonRouteMetricFormRequest;
use App\Http\Requests\Metric\APIMetricFormRequest;
use App\Models\DungeonRoute;
use App\Service\Metric\MetricServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

class APIMetricController extends Controller
{
    /**
     * @param APIMetricFormRequest $request
     * @param MetricServiceInterface $metricService
     * @return Response
     * @throws AuthorizationException
     */
    public function store(APIMetricFormRequest $request, MetricServiceInterface $metricService)
    {
        $validated = $request->validated();

        $metricService->storeMetric($request['model_id'], $request['model_class'], $validated['category'], $validated['tag'], $validated['value']);

        return response()->noContent();
    }

    /**
     * @param APIDungeonRouteMetricFormRequest $request
     * @param DungeonRoute $dungeonRoute
     * @param MetricServiceInterface $metricService
     * @return Response
     */
    public function storeDungeonRoute(APIDungeonRouteMetricFormRequest $request, DungeonRoute $dungeonRoute, MetricServiceInterface $metricService)
    {
        $validated = $request->validated();

        $metricService->storeMetricByModel($dungeonRoute, $validated['category'], $validated['tag'], $validated['value']);

        return response()->noContent();
    }
}
