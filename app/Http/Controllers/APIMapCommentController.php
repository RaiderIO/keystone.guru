<?php

namespace App\Http\Controllers;

use App\Events\MapCommentChangedEvent;
use App\Events\MapCommentDeletedEvent;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsMapComments;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\DungeonRoute;
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
     * @param DungeonRoute $dungeonroute
     * @return array
     * @throws \Exception
     */
    function store(Request $request, DungeonRoute $dungeonroute)
    {
        $this->authorize('edit', $dungeonroute);

        /** @var MapComment $mapComment */
        $mapComment = MapComment::findOrNew($request->get('id'));
        $isAdmin = Auth::check() && Auth::user()->hasRole('admin');

        // Only admins may make global comments for all routes
        $mapComment->always_visible = $isAdmin ? $request->get('always_visible', 0) : 0;
        $mapComment->floor_id = $request->get('floor_id');
        $mapComment->dungeon_route_id = $dungeonroute->id;
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
            broadcast(new MapCommentChangedEvent($dungeonroute, $mapComment, Auth::user()));

            $result = ['id' => $mapComment->id];
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param MapComment $mapcomment
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     */
    function delete(Request $request, DungeonRoute $dungeonroute, MapComment $mapcomment)
    {
        // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's route
        $this->authorize('edit', $dungeonroute);

        try {
            if ($mapcomment->delete()) {
                broadcast(new MapCommentDeletedEvent($dungeonroute, $mapcomment, Auth::user()));
                $result = ['result' => 'success'];
            } else {
                $result = ['result' => 'error'];
            }
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
