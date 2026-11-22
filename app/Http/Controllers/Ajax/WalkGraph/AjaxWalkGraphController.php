<?php

namespace App\Http\Controllers\Ajax\WalkGraph;

use App\Http\Controllers\Controller;
use App\Http\Requests\WalkGraph\AjaxWalkGraphAreaFormRequest;
use App\Models\Floor\Floor;
use App\Service\WalkGraph\WalkGraphServiceInterface;
use Illuminate\Http\JsonResponse;

class AjaxWalkGraphController extends Controller
{
    public function show(Floor $floor, WalkGraphServiceInterface $walkGraphService): JsonResponse
    {
        return response()->json($walkGraphService->getFloorSegments($floor));
    }

    public function area(
        AjaxWalkGraphAreaFormRequest $request,
        Floor                        $floor,
        WalkGraphServiceInterface    $walkGraphService,
    ): JsonResponse {
        $mappingVersion = $floor->facade ? $request->getMappingVersion() : null;

        return response()->json($mappingVersion === null ?
            $walkGraphService->getFloorArea($floor) :
            $walkGraphService->getFacadeFloorArea($mappingVersion, $floor));
    }
}
