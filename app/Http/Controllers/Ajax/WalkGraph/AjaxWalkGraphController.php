<?php

namespace App\Http\Controllers\Ajax\WalkGraph;

use App\Http\Controllers\Controller;
use App\Models\Floor\Floor;
use App\Service\WalkGraph\WalkGraphServiceInterface;
use Illuminate\Http\JsonResponse;

class AjaxWalkGraphController extends Controller
{
    public function show(Floor $floor, WalkGraphServiceInterface $walkGraphService): JsonResponse
    {
        return response()->json($walkGraphService->getFloorSegments($floor));
    }

    public function area(Floor $floor, WalkGraphServiceInterface $walkGraphService): JsonResponse
    {
        return response()->json($walkGraphService->getFloorArea($floor));
    }
}
