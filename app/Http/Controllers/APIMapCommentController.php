<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsMapComments;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\MapComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIMapCommentController extends Controller
{
    use PublicKeyDungeonRoute;
    use ChecksForDuplicates;
    use ListsMapComments;

    function list(Request $request)
    {
        return $this->listMapComments(
            $request->get('floor_id'),
            $request->get('dungeonroute', null)
        );
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    function store(Request $request)
    {
        $dungeonRoutePublicKey = $request->get('dungeonroute', null);

        /** @var MapComment $mapComment */
        $mapComment = MapComment::findOrNew($request->get('id'));
        $isAdmin = Auth::check() && Auth::user()->hasRole('admin');

        $dungeonRouteId = -1;
        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($dungeonRoutePublicKey);
            $dungeonRouteId = $dungeonRoute->id;
        } catch (\Exception $ex) {
            // It's okay if we're an admin, they can add comments without a route (global comments on the floor)
            if (!$isAdmin) {
                // I don't like multiple returns but it's much easier/cleaner this way
                return response('Not found', Http::NOT_FOUND);
            }
        }

        // Only admins may make global comments for all routes
        $mapComment->always_visible = $isAdmin ? $request->get('always_visible', 0) : 0;
        $mapComment->floor_id = $request->get('floor_id');
        $mapComment->dungeon_route_id = $dungeonRouteId;
        $mapComment->game_icon_id = -1;
        $mapComment->comment = $request->get('comment', '');
        $mapComment->lat = $request->get('lat');
        $mapComment->lng = $request->get('lng');

        if (!$mapComment->exists) {
            $this->checkForDuplicate($mapComment);
        }

        if (!$mapComment->save()) {
            throw new \Exception("Unable to save map comment!");
        } else {
            $result = ['id' => $mapComment->id];
        }

        return $result;
    }

    /**
     * @param Request $request
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    function delete(Request $request)
    {
        try {
            /** @var MapComment $mapComment */
            $mapComment = MapComment::findOrFail($request->get('id'));

            $mapComment->delete();
            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
