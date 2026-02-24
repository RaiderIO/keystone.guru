<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteSearchFormRequest;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRoute\Dtos\DungeonRouteSearchFilter;
use App\Service\DungeonRoute\DungeonRouteSearchServiceInterface;
use App\Service\MapContext\MapContextServiceInterface;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use function response;
use Teapot\StatusCode;

class AjaxDungeonRouteSearchController extends Controller
{
    public function get(
        AjaxDungeonRouteSearchFormRequest  $request,
        GameVersion                        $gameVersion,
        Dungeon                            $dungeon,
        DungeonRouteSearchServiceInterface $dungeonRouteSearchService,
    ): string {
        try {
            $result = $dungeonRouteSearchService->search(
                DungeonRouteSearchFilter::fromArray(
                    $dungeon->getCurrentMappingVersionForGameVersion($gameVersion),
                    $request->validated(),
                ),
            );

            if ($result->isEmpty()) {
                return response()->noContent();
            } else {
                return view('common.dungeonroute.cardlist', [
                    'currentAffixGroup' => null,
                    'dungeonroutes'     => $result,
                    'showAffixes'       => true,
                    'showDungeonImage'  => false,
                    'orientation'       => 'horizontal_row',
                ])->render();
            }
        } catch (Exception $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                StatusCode::INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function getMapContext(
        FormRequest                $request,
        DungeonRoute               $dungeonRoute,
        MapContextServiceInterface $mapContextService,
    ): JsonResponse {
        try {
            Gate::authorize('view', $dungeonRoute);

            return response()->json(
                $mapContextService->createMapContextDungeonRoute($dungeonRoute, User::getCurrentUserMapFacadeStyle())->toArray(),
            );
        } catch (Exception $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                StatusCode::INTERNAL_SERVER_ERROR,
            );
        }
    }
}
