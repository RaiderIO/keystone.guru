<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Cache;

use App\Http\Controllers\Controller;
use App\Jobs\DropCaches;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class APICacheController extends Controller
{
    /**
     * @OA\Post(
     *     operationId="dropCache",
     *     path="/api/v1/cache/drop",
     *     summary="Queue dropping of all application caches",
     *     tags={"Cache"},
     *
     *     @OA\Response(response=200, description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok")
     *         )
     *     )
     * )
     */
    public function drop(Request $request): JsonResponse
    {
        DropCaches::dispatch();

        return response()->json(['status' => 'ok']);
    }
}
