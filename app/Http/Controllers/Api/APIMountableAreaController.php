<?php

namespace App\Http\Controllers\Api;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Requests\MountableArea\MountableAreaFormRequest;
use App\Models\MountableArea;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIMountableAreaController extends Controller
{
    use ChangesMapping;

    /**
     * @param MountableAreaFormRequest $request
     * @return MountableArea
     * @throws Exception
     */
    function store(MountableAreaFormRequest $request)
    {
        /** @var MountableArea $mountableArea */
        $mountableArea = MountableArea::findOrNew($request->get('id'));

        $beforeMountableArea = clone $mountableArea;

        $mountableArea->floor_id      = (int)$request->get('floor_id');
        $mountableArea->vertices_json = json_encode($request->get('vertices'));

        // Upon successful save!
        if ($mountableArea->save()) {
            if (Auth::check()) {
                broadcast(new ModelChangedEvent($mountableArea->floor->dungeon, Auth::getUser(), $mountableArea));
            }

            // Trigger mapping changed event so the mapping gets saved across all environments
            $this->mappingChanged($beforeMountableArea, $mountableArea);
        } else {
            throw new Exception('Unable to save pack!');
        }

        return $mountableArea;
    }

    /**
     * @param Request $request
     * @param MountableArea $mountablearea
     * @return array|ResponseFactory|Response
     */
    function delete(Request $request, MountableArea $mountablearea)
    {
        try {
            if ($mountablearea->delete()) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($mountablearea->floor->dungeon, Auth::getUser(), $mountablearea));
                }

                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($mountablearea, null);
            }
            $result = response()->noContent();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
