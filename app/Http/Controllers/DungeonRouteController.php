<?php

namespace App\Http\Controllers;

use App\Http\Requests\DungeonRouteFormRequest;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\UserReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DungeonRouteController extends Controller
{
    /**
     * Show a page for creating a new dungeon route.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new()
    {
        $result = null;

        $user = Auth::user();
        if ($user->canCreateDungeonRoute()) {
            $result = view('dungeonroute.new', ['dungeons' => Dungeon::all(), 'headerTitle' => __('New dungeonroute')]);
        } else {
            $result = view('dungeonroute.limitreached', ['headerTitle' => __('Limit reached')]);
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function view(Request $request, DungeonRoute $dungeonroute)
    {
        $user = Auth::user();
        $currentReport = null;
        if ($user !== null) {
            // Find any currently active report the user has made
            $currentReport = UserReport::where('author_id', $user->id)
                ->where('context', $dungeonroute->getReportContext())
                ->where('category', 'dungeonroute')
                ->where('handled', 0)
                ->first();
        }

        return view('dungeonroute.view', [
            'model' => $dungeonroute,
            'current_report' => $currentReport
        ]);
    }

    /**
     * @param DungeonRouteFormRequest $request
     * @param DungeonRoute $dungeonroute
     * @return mixed
     * @throws \Exception
     */
    public function store(DungeonRouteFormRequest $request, DungeonRoute $dungeonroute = null)
    {
        if ($dungeonroute === null) {
            $dungeonroute = new DungeonRoute();
        }

        // May fail
        if (!$dungeonroute->saveFromRequest($request)) {
            abort(500, __('Unable to save dungeonroute'));
        }

        return $dungeonroute;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request, DungeonRoute $dungeonroute)
    {
        return view('dungeonroute.edit', ['model' => $dungeonroute, 'headerTitle' => __('Edit dungeonroute')]);
    }

    /**
     * Override to give the type hint which is required.
     *
     * @param DungeonRouteFormRequest $request
     * @param DungeonRoute $dungeonroute
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function update(DungeonRouteFormRequest $request, DungeonRoute $dungeonroute)
    {
        // Store it and show the edit page again
        $dungeonroute = $this->store($request, $dungeonroute);

        // Message to the user
        \Session::flash('status', __('Dungeonroute updated'));

        // Display the edit page
        return $this->edit($request, $dungeonroute);
    }

    /**
     * @param DungeonRouteFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function savenew(DungeonRouteFormRequest $request)
    {
        // Store it and show the edit page
        $dungeonroute = $this->store($request);

        // Message to the user
        \Session::flash('status', __('Dungeonroute created'));

        return redirect()->route('dungeonroute.edit', ["dungeonroute" => $dungeonroute]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\
     */
    public function list()
    {
        return view('dungeonroute.list', ['models' => DungeonRoute::all()]);
    }
}
