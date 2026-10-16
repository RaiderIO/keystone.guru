<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\DungeonTransport\DungeonTransportChangedEvent;
use App\Events\Models\DungeonTransport\DungeonTransportDeletedEvent;
use App\Events\Models\ModelChangedEvent;
use App\Http\Requests\DungeonTransport\DungeonTransportFormRequest;
use App\Logic\Structs\LatLng;
use App\Models\DungeonTransport;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Repositories\Interfaces\DungeonTransportRepositoryInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxDungeonTransportController extends AjaxMappingModelBaseController
{
    /**
     * @throws Throwable
     */
    public function store(
        DungeonTransportFormRequest $request,
        CoordinatesServiceInterface $coordinatesService,
        MappingVersion              $mappingVersion,
        ?DungeonTransport           $dungeonTransport = null,
    ): DungeonTransport|Model {
        $validated = $request->validated();

        if (array_key_exists('path_vertices_json', $validated)) {
            $validated['path_vertices_json'] = $validated['path_vertices_json'] === null ? null : json_encode(
                $this->convertFacadePathVertices($coordinatesService, $mappingVersion, $validated['path_vertices_json']),
            );
        }

        return $this->storeModel($coordinatesService, $mappingVersion, $validated, DungeonTransport::class, $dungeonTransport);
    }

    public function delete(
        Request                             $request,
        DungeonTransportRepositoryInterface $dungeonTransportRepository,
        MappingVersion                      $mappingVersion,
        DungeonTransport                    $dungeonTransport,
    ): Response {
        try {
            $dungeon = $dungeonTransport->floor->dungeon;
            if ($dungeonTransport->delete()) {
                $dungeonTransportRepository->unlinkTransportsLinkedTo($dungeonTransport->id);

                if (Auth::check()) {
                    /** @var User $user */
                    $user = Auth::getUser();

                    try {
                        broadcast(new DungeonTransportDeletedEvent($dungeon, $user, $dungeonTransport));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
                }

                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($dungeonTransport, null);
            }

            $result = response()->noContent();
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param DungeonTransport $model
     */
    protected function restoreFacadeAttributes(
        CoordinatesServiceInterface $coordinatesService,
        MappingVersion              $mappingVersion,
        Model                       $model,
    ): void {
        $model->convertPathVerticesToFacade($coordinatesService, $mappingVersion);
    }

    protected function getModelChangedEvent(
        CoordinatesServiceInterface $coordinatesService,
        Model                       $context,
        User                        $user,
        Model                       $model,
    ): ModelChangedEvent {
        return new DungeonTransportChangedEvent($context, $user, $model);
    }

    /**
     * Converts each vertex on a facade floor back onto the floor it belongs to.
     *
     * @param  array<int, array{lat: float|string, lng: float|string, floor_id: int|string}> $pathVertices
     * @return array<int, array{lat: float, lng: float, floor_id: int}>
     */
    private function convertFacadePathVertices(
        CoordinatesServiceInterface $coordinatesService,
        MappingVersion              $mappingVersion,
        array                       $pathVertices,
    ): array {
        $floorsById = $mappingVersion->dungeon->floors->keyBy('id');

        $result = [];
        foreach ($pathVertices as $pathVertex) {
            /** @var Floor $floor */
            $floor  = $floorsById->get((int)$pathVertex['floor_id']);
            $latLng = new LatLng((float)$pathVertex['lat'], (float)$pathVertex['lng'], $floor);

            if ($floor->facade) {
                $latLng = $coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $latLng);
            }

            $result[] = [
                'lat'      => $latLng->getLat(DungeonTransport::PATH_VERTEX_PRECISION),
                'lng'      => $latLng->getLng(DungeonTransport::PATH_VERTEX_PRECISION),
                'floor_id' => $latLng->getFloor()?->id ?? $floor->id,
            ];
        }

        return $result;
    }
}
