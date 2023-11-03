<?php

namespace App\Http\Controllers\Ajax\Floor;

use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Ajax\AjaxMappingModelBaseController;
use App\Http\Requests\Floor\FloorUnionAreaFormRequest;
use App\Models\Floor\FloorUnionArea;
use DB;
use Exception;
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
     * @param FloorUnionAreaFormRequest $request
     * @param FloorUnionArea|null       $floorUnionArea
     *
     * @return FloorUnionArea|Model
     * @throws Exception
     * @throws Throwable
     */
    public function store(FloorUnionAreaFormRequest $request, FloorUnionArea $floorUnionArea = null): FloorUnionArea
    {
        $validated = $request->validated();

        $validated['vertices_json'] = json_encode($request->get('vertices'));
        unset($validated['vertices']);

        return $this->storeModel($validated, FloorUnionArea::class, $floorUnionArea);
    }

    /**
     * @param Request        $request
     * @param FloorUnionArea $floorUnionArea
     *
     * @return Response|ResponseFactory
     * @throws Throwable
     */
    public function delete(Request $request, FloorUnionArea $floorUnionArea)
    {
        return DB::transaction(function () use ($request, $floorUnionArea) {
            try {
                if ($floorUnionArea->delete()) {
                    // Trigger mapping changed event so the mapping gets saved across all environments
                    $this->mappingChanged($floorUnionArea, null);

                    if (Auth::check()) {
                        broadcast(new ModelDeletedEvent($floorUnionArea->floor->dungeon, Auth::getUser(), $floorUnionArea));
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