<?php

namespace App\Http\Controllers\Ajax\Floor;

use App\Events\Models\FloorUnionArea\FloorUnionAreaChangedEvent;
use App\Events\Models\FloorUnionArea\FloorUnionAreaDeletedEvent;
use App\Events\Models\ModelChangedEvent;
use App\Http\Controllers\Ajax\AjaxMappingModelBaseController;
use App\Http\Requests\Floor\FloorUnionAreaFormRequest;
use App\Models\Floor\FloorUnionArea;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use DB;
use Exception;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxFloorUnionAreaController extends AjaxMappingModelBaseController
{
    /**
     * @throws Throwable
     */
    public function store(
        FloorUnionAreaFormRequest   $request,
        CoordinatesServiceInterface $coordinatesService,
        MappingVersion              $mappingVersion,
        ?FloorUnionArea             $floorUnionArea = null,
    ): FloorUnionArea|Model {
        $validated = $request->validated();

        $validated['vertices_json'] = json_encode($request->get('vertices'));
        unset($validated['vertices']);

        return $this->storeModel($coordinatesService, $mappingVersion, $validated, FloorUnionArea::class, $floorUnionArea);
    }

    /**
     * @return Response|ResponseFactory
     *
     * @throws Throwable
     */
    public function delete(Request $request, MappingVersion $mappingVersion, FloorUnionArea $floorUnionArea)
    {
        // route:cache serializes this method; a body whose only $this usage sits inside a
        // nested closure is reconstructed unbound. Delegating keeps a top-level $this read
        // here, and the closures below compile normally inside a regular method (#4329).
        return $this->deleteFloorUnionArea($request, $mappingVersion, $floorUnionArea);
    }

    /**
     * @return Response|ResponseFactory
     *
     * @throws Throwable
     */
    private function deleteFloorUnionArea(Request $request, MappingVersion $mappingVersion, FloorUnionArea $floorUnionArea)
    {
        return DB::transaction(function () use ($floorUnionArea) {
            try {
                if ($floorUnionArea->delete()) {
                    // Trigger mapping changed event so the mapping gets saved across all environments
                    $this->mappingChanged($floorUnionArea, null);

                    if (Auth::check()) {
                        /** @var User $user */
                        $user = Auth::getUser();

                        try {
                            broadcast(new FloorUnionAreaDeletedEvent($floorUnionArea->floor->dungeon, $user, $floorUnionArea));
                        } catch (BroadcastException) {
                            // Ignore broadcast failures
                        }
                    }
                }

                $result = response()->noContent();
            } catch (Exception) {
                $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
            }

            return $result;
        });
    }

    protected function getModelChangedEvent(
        CoordinatesServiceInterface $coordinatesService,
        Model                       $context,
        User                        $user,
        Model                       $model,
    ): ModelChangedEvent {
        return new FloorUnionAreaChangedEvent($context, $user, $model);
    }
}
