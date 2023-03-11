<?php

namespace App\Http\Controllers\Api;

use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\APIMappingModelBaseController;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Requests\MountableArea\MountableAreaFormRequest;
use App\Models\MountableArea;
use DB;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;
use Throwable;

class APIMountableAreaController extends APIMappingModelBaseController
{
    use ChangesMapping;

    /**
     * @param MountableAreaFormRequest $request
     * @param MountableArea|null $mountableArea
     * @return MountableArea|Model
     * @throws Exception
     * @throws Throwable
     */
    public function store(MountableAreaFormRequest $request, MountableArea $mountableArea = null): MountableArea
    {
        $validated = $request->validated();

        $validated['vertices_json'] = json_encode($request->get('vertices'));
        unset($validated['vertices']);

        return $this->storeModel($validated, MountableArea::class, $mountableArea);
    }

    /**
     * @param Request $request
     * @param MountableArea $mountableArea
     * @return Response|ResponseFactory
     * @throws Throwable
     */
    public function delete(Request $request, MountableArea $mountableArea)
    {
        return DB::transaction(function () use ($request, $mountableArea) {
            try {
                if ($mountableArea->delete()) {
                    // Trigger mapping changed event so the mapping gets saved across all environments
                    $this->mappingChanged($mountableArea, null);

                    if (Auth::check()) {
                        broadcast(new ModelDeletedEvent($mountableArea->floor->dungeon, Auth::getUser(), $mountableArea));
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
