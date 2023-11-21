<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\SavesPolylines;
use App\Http\Requests\Path\APIPathFormRequest;
use App\Models\DungeonRoute;
use App\Models\Path;
use App\Models\Polyline;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Teapot\StatusCode\Http;

class AjaxPathController extends Controller
{
    use SavesPolylines;

    /**
     * @param APIPathFormRequest          $request
     * @param CoordinatesServiceInterface $coordinatesService
     * @param DungeonRoute                $dungeonRoute
     * @param Path|null                   $path
     * @return Path
     * @throws AuthorizationException
     */
    function store(
        APIPathFormRequest          $request,
        CoordinatesServiceInterface $coordinatesService,
        DungeonRoute                $dungeonRoute,
        ?Path                       $path = null)
    {
        $dungeonRoute = optional($path)->dungeonRoute ?? $dungeonRoute;

        $this->authorize('edit', $dungeonRoute);

        $validated = $request->validated();

        if ($path === null) {
            $path    = Path::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $validated['floor_id'],
                'polyline_id'      => -1,
            ]);
            $success = $path instanceof Path;
        } else {
            $success = $path->update([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $validated['floor_id'],
            ]);
        }

        try {
            if ($success) {
                // Create a new polyline and save it
                $changedFloor = null;
                $polyline     = $this->savePolyline(
                    $coordinatesService,
                    $dungeonRoute->mappingVersion,
                    Polyline::findOrNew($path->polyline_id),
                    $path,
                    $validated['polyline'],
                    $changedFloor
                );

                // Couple the path to the polyline
                $path->update([
                    'polyline_id' => $polyline->id,
                    'floor_id'    => optional($changedFloor)->id ?? $path->floor_id,
                ]);

                // Load the polyline so it can be echoed back to the user
                $path->load(['polyline']);

                // Set or unset the linked awakened obelisks now that we have an ID
                $path->setLinkedAwakenedObeliskByMapIconId($validated['linked_awakened_obelisk_id']);

                // Something's updated; broadcast it
                if (Auth::check()) {
                    broadcast(new ModelChangedEvent($dungeonRoute, Auth::user(), $path));
                }

                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();
            } else {
                throw new \Exception('Unable to save path!');
            }

            $result = $path;
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param Request      $request
     * @param DungeonRoute $dungeonRoute
     * @param Path         $path
     * @return array|ResponseFactory|Response
     * @throws AuthorizationException
     */
    function delete(Request $request, DungeonRoute $dungeonRoute, Path $path)
    {
        $dungeonRoute = $path->dungeonRoute;

        // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's path
        $this->authorize('edit', $dungeonRoute);

        try {
            if ($path->delete()) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($dungeonRoute, Auth::user(), $path));
                }

                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();

                $result = response()->noContent();
            } else {
                $result = response('Unable to delete Path', Http::INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
