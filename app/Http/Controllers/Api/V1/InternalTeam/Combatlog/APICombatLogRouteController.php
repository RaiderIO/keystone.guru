<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Combatlog;

use App\Http\Controllers\Controller;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Teapot\StatusCode;

class APICombatLogRouteController extends Controller
{
    /**
     * @OA\Get(
     *     operationId="getCombatLogRoutePostBody",
     *     path="/api/v1/combatlog/route/{dungeonRoute}/post-body",
     *     summary="The JSON body the Auto Route Creator was originally POSTed for this combat log route, so the run can be replayed locally",
     *     tags={"CombatLog"},
     *
     *     @OA\Parameter(name="dungeonRoute", in="path", required=true, description="Dungeon route public key", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="The stored request body, verbatim", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Not an admin"),
     *     @OA\Response(response=404, description="Unknown route, or the route has no stored post body"),
     * )
     */
    public function postBody(DungeonRoute $dungeonRoute): Response|JsonResponse
    {
        $postBody = ChallengeModeRun::query()
            ->where('dungeon_route_id', $dungeonRoute->id)
            ->latest('id')
            ->first()
            ?->challengeModeRunData
            ?->post_body;

        if ($postBody === null || $postBody === '') {
            return response()->json([
                'error' => __('controller.apicombatlogroute.error.no_post_body'),
            ], StatusCode::NOT_FOUND);
        }

        // Already JSON text - hand it over verbatim rather than decoding and re-encoding it
        return response($postBody, StatusCode::OK)->header('Content-Type', 'application/json');
    }
}
