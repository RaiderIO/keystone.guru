<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsDungeonStartMarkers;
use App\Models\DungeonStartMarker;
use Illuminate\Http\Request;
use Teapot\StatusCode\Http;

class APIDungeonStartMarkerController extends Controller
{
    use ChecksForDuplicates;
    use ListsDungeonStartMarkers;

    function list(Request $request)
    {
        return $this->listDungeonStartMarkers($request->get('floor_id'));
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    function store(Request $request)
    {
        /** @var DungeonStartMarker $dungeonStartMarker */
        $dungeonStartMarker = DungeonStartMarker::findOrNew($request->get('id'));

        $dungeonStartMarker->floor_id = $request->get('floor_id');
        $dungeonStartMarker->lat = $request->get('lat');
        $dungeonStartMarker->lng = $request->get('lng');

        // Find out of there is a duplicate
        $this->checkForDuplicate($dungeonStartMarker);

        if (!$dungeonStartMarker->save()) {
            throw new \Exception("Unable to save dungeon start marker!");
        }

        return ['id' => $dungeonStartMarker->id];
    }

    function delete(Request $request)
    {
        try {
            /** @var DungeonStartMarker $dungeonStartMarker */
            $dungeonStartMarker = DungeonStartMarker::findOrFail($request->get('id'));

            $dungeonStartMarker->delete();
            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
