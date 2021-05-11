<?php

namespace App\Http\Controllers;

use App\Logic\MapContext\MapContextDungeonRoute;
use App\Models\DungeonRoute;
use App\Models\Floor;
use App\Models\LiveSession;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LiveSessionController extends Controller
{
    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param LiveSession $livesession
     * @return Application|Factory|View|RedirectResponse
     * @throws AuthorizationException
     */
    public function view(Request $request, DungeonRoute $dungeonroute, LiveSession $livesession)
    {
        $defaultFloor = $dungeonroute->dungeon->floors()->where('default', true)->first();
        return $this->viewfloor($request, $dungeonroute, $livesession, optional($defaultFloor)->index ?? '1');
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param LiveSession $livesession
     * @param string $floorIndex
     * @return Application|Factory|View|RedirectResponse
     * @throws AuthorizationException
     */
    public function viewfloor(Request $request, DungeonRoute $dungeonroute, LiveSession $livesession, string $floorIndex)
    {
        $this->authorize('view', $dungeonroute);

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorIndex)->first();

        if ($floor === null) {
            return redirect()->route('dungeonroute.livesession.view', [
                'dungeonroute' => $dungeonroute->public_key,
                'livesession'  => $livesession->public_key
            ]);
        } else {
            return view('dungeonroute.livesession.view', [
                'model'      => $dungeonroute,
                'floor'      => $floor,
                'mapContext' => (new MapContextDungeonRoute($dungeonroute, $floor))->toArray()
            ]);
        }
    }
}
