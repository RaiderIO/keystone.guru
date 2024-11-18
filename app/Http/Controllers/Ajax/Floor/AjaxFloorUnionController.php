<?php

namespace App\Http\Controllers\Ajax\Floor;

use App\Events\Models\FloorUnion\FloorUnionChangedEvent;
use App\Events\Models\FloorUnion\FloorUnionDeletedEvent;
use App\Events\Models\ModelChangedEvent;
use App\Http\Controllers\Ajax\AjaxMappingModelBaseController;
use App\Http\Requests\Floor\FloorUnionFormRequest;
use App\Models\Floor\FloorUnion;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
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

class AjaxFloorUnionController extends AjaxMappingModelBaseController
{
    protected function shouldCallMappingChanged(?MappingModelInterface $beforeModel, ?MappingModelInterface $afterModel): bool
    {
        return false;
    }

    /**
     * @throws Throwable
     */
    public function store(
        FloorUnionFormRequest       $request,
        CoordinatesServiceInterface $coordinatesService,
        MappingVersion              $mappingVersion,
        ?FloorUnion                 $floorUnion = null
    ): FloorUnion|Model {
        $validated = $request->validated();

        return $this->storeModel($coordinatesService, $mappingVersion, $validated, FloorUnion::class, $floorUnion);
    }

    /**
     * @return Response|ResponseFactory
     *
     * @throws Throwable
     */
    public function delete(Request $request, MappingVersion $mappingVersion, FloorUnion $floorUnion)
    {
        return DB::transaction(static function () use ($floorUnion) {
            try {
                if ($floorUnion->delete()) {
                    if (Auth::check()) {
                        /** @var User $user */
                        $user = Auth::getUser();
                        broadcast(new FloorUnionDeletedEvent($floorUnion->floor->dungeon, $user, $floorUnion));
                    }
                }

                $result = response()->noContent();
            } catch (Exception) {
                $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
            }

            return $result;
        });
    }

    protected function getModelChangedEvent(CoordinatesServiceInterface $coordinatesService, Model $context, User $user, Model $model): ModelChangedEvent
    {
        return new FloorUnionChangedEvent($context, $user, $model);
    }


}
