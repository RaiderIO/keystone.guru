<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Cache;

use App\Http\Controllers\Controller;
use App\Service\Cache\CacheServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class APICacheController extends Controller
{
    /**
     * @OA\Post(
     *     operationId="dropCache",
     *     path="/api/v1/cache/drop",
     *     summary="Drop all application caches",
     *     tags={"Cache"},
     *
     *     @OA\Response(response=200, description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok")
     *         )
     *     )
     * )
     */
    public function drop(Request $request, CacheServiceInterface $cacheService): JsonResponse
    {
        ini_set('max_execution_time', -1);

        $cacheService->dropCaches();

        Artisan::call('modelCache:clear');

        Artisan::call('keystoneguru:view', ['operation' => 'cache']);

        return response()->json(['status' => 'ok']);
    }
}
