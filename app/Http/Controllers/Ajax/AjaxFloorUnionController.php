<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Model\ModelDeletedEvent;
use App\Http\Requests\Floor\FloorUnionFormRequest;
use App\Models\Floor\FloorUnion;
use App\Models\Mapping\MappingModelInterface;
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
     * @param FloorUnionFormRequest $request
     * @param FloorUnion|null       $floorUnion
     * @return FloorUnion|Model
     * @throws Exception
     * @throws Throwable
     */
    public function store(FloorUnionFormRequest $request, FloorUnion $floorUnion = null): FloorUnion
    {
        $validated = $request->validated();

        return $this->storeModel($validated, FloorUnion::class, $floorUnion);
    }

    /**
     * @param Request    $request
     * @param FloorUnion $floorUnion
     * @return Response|ResponseFactory
     * @throws Throwable
     */
    public function delete(Request $request, FloorUnion $floorUnion)
    {
        return DB::transaction(function () use ($request, $floorUnion) {
            try {
                if ($floorUnion->delete()) {
                    if (Auth::check()) {
                        broadcast(new ModelDeletedEvent($floorUnion->floor->dungeon, Auth::getUser(), $floorUnion));
                    }
                }
                $result = response()->noContent();
            } catch (Exception $ex) {
                $result = response('Not found', Http::NOT_FOUND);
            }

            return $result;
        });
    }
}
