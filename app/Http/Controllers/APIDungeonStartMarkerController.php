<?php

namespace App\Http\Controllers;

use App\Models\DungeonStartMarker;
use Illuminate\Http\Request;
use Teapot\StatusCode\Http;

class APIDungeonStartMarkerController extends Controller
{
    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        return DungeonStartMarker::all()->where('floor_id', '=', $floorId);
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
