<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteSearchNewFormRequest;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\File;
use App\Models\GameVersion\GameVersion;
use App\Models\User;
use App\Repositories\Database\DungeonRoute\Dtos\KillZoneEnemyForces;
use App\Repositories\Interfaces\DungeonRoute\Dtos\DungeonRouteSearchFilter;
use App\Service\DungeonRoute\DungeonRouteKillZoneServiceInterface;
use App\Service\DungeonRoute\DungeonRouteSearchServiceInterface;
use App\Service\MapContext\MapContextServiceInterface;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Teapot\StatusCode;

class AjaxDungeonRouteSearchController extends Controller
{
    public function get(
        AjaxDungeonRouteSearchNewFormRequest $request,
        GameVersion                          $gameVersion,
        Dungeon                              $dungeon,
        DungeonRouteSearchServiceInterface   $dungeonRouteSearchService,
        DungeonRouteKillZoneServiceInterface $dungeonRouteKillZoneService,
    ): Response {
        try {
            $result = $dungeonRouteSearchService->search(
                DungeonRouteSearchFilter::fromArray(
                    $dungeon->getCurrentMappingVersionForGameVersion($gameVersion),
                    $request->validated(),
                ),
            );

            if ($result->isEmpty()) {
                return response()->noContent(); // 204, empty body
            }

            $forcesByRouteId = $dungeonRouteKillZoneService->getEnemyForcesPerKillZoneForRoutes($result);

            return response()->json(
                $result->map(static fn(DungeonRoute $dungeonRoute): array => self::toSearchResult(
                    $dungeonRoute,
                    $forcesByRouteId->get($dungeonRoute->id, collect()),
                ))->values(),
            );
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

    /**
     * A route in the shape of an /ajax/routes row, as far as the route picker's row reads it.
     *
     * @param  Collection<int, KillZoneEnemyForces> $pullForces
     * @return array<string, mixed>
     */
    private static function toSearchResult(DungeonRoute $dungeonRoute, Collection $pullForces): array
    {
        return [
            'public_key'                    => $dungeonRoute->public_key,
            'title'                         => $dungeonRoute->title,
            'published'                     => $dungeonRoute->published,
            'level_min'                     => $dungeonRoute->level_min,
            'level_max'                     => $dungeonRoute->level_max,
            'teeming'                       => $dungeonRoute->teeming,
            'views'                         => $dungeonRoute->views,
            'rating'                        => $dungeonRoute->rating,
            'rating_count'                  => $dungeonRoute->rating_count,
            'enemy_forces'                  => $dungeonRoute->enemy_forces,
            'enemy_forces_required'         => $dungeonRoute->mappingVersion->enemy_forces_required,
            'enemy_forces_required_teeming' => $dungeonRoute->mappingVersion->enemy_forces_required_teeming,
            'has_thumbnail'                 => $dungeonRoute->has_thumbnail,
            'thumbnails'                    => $dungeonRoute->thumbnails
                ->map(static fn(File $file): array => ['url' => $file->getURL()])
                ->values()
                ->all(),
            'dungeon' => [
                'id'        => $dungeonRoute->dungeon->id,
                'name'      => $dungeonRoute->dungeon->name,
                'key'       => $dungeonRoute->dungeon->key,
                'expansion' => ['shortname' => $dungeonRoute->dungeon->expansion->shortname],
            ],
            'pull_forces' => $pullForces
                ->map(static fn(KillZoneEnemyForces $pull): array => [
                    'enemy_forces' => $pull->enemyForces,
                    'has_boss'     => $pull->hasBoss,
                ])
                ->values()
                ->all(),
        ];
    }
}
