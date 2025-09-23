<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\ModelChangedEvent;
use App\Events\Models\MountableArea\MountableAreaChangedEvent;
use App\Events\Models\MountableArea\MountableAreaDeletedEvent;
use App\Http\Requests\MountableArea\MountableAreaFormRequest;
use App\Models\Mapping\MappingVersion;
use App\Models\MountableArea;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use DB;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxMountableAreaController extends AjaxMappingModelBaseController
{
    /**
     * @return MountableArea|Model
     *
     * @throws Exception
     * @throws Throwable
     */
    public function store(
        MountableAreaFormRequest    $request,
        CoordinatesServiceInterface $coordinatesService,
        MappingVersion              $mappingVersion,
        ?MountableArea              $mountableArea = null,
    ): MountableArea {
        $validated = $request->validated();

        $validated['vertices_json'] = json_encode($request->get('vertices'));
        unset($validated['vertices']);

        return $this->storeModel($coordinatesService, $mappingVersion, $validated, MountableArea::class, $mountableArea);
    }

    /**
     * @return Response|ResponseFactory
     *
     * @throws Throwable
     */
    public function delete(Request $request, MountableArea $mountableArea)
    {
        return DB::transaction(function () use ($mountableArea) {
            try {
                if ($mountableArea->delete()) {
                    // Trigger mapping changed event so the mapping gets saved across all environments
                    $this->mappingChanged($mountableArea, null);

                    if (Auth::check()) {
                        /** @var User $user */
                        $user = Auth::user();
                        broadcast(new MountableAreaDeletedEvent($mountableArea->floor->dungeon, $user, $mountableArea));
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
        return new MountableAreaChangedEvent($context, $user, $model);
    }
}
