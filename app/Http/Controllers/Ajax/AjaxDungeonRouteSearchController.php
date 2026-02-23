<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteSearchFormRequest;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Repositories\Interfaces\DungeonRoute\Dtos\DungeonRouteSearchFilter;
use App\Service\DungeonRoute\DungeonRouteSearchServiceInterface;
use App\Service\RaiderIO\Exceptions\InvalidApiResponseException;
use Illuminate\Http\JsonResponse;
use Teapot\StatusCode;

class AjaxDungeonRouteSearchController extends Controller
{
    public function get(
        AjaxDungeonRouteSearchFormRequest  $request,
        GameVersion                        $gameVersion,
        Dungeon                            $dungeon,
        DungeonRouteSearchServiceInterface $dungeonRouteSearchService,
    ): JsonResponse {
        try {
            return \response()->json(
                $dungeonRouteSearchService->search(
                    DungeonRouteSearchFilter::fromArray(
                        $dungeon->getCurrentMappingVersionForGameVersion($gameVersion),
                        $request->validated(),
                    ),
                )->toArray(),
                StatusCode::OK,
            );
        } catch (InvalidApiResponseException $exception) {
            return \response()->json(
                ['message' => $exception->getMessage()],
                StatusCode::INTERNAL_SERVER_ERROR,
            );
        }
    }
}
