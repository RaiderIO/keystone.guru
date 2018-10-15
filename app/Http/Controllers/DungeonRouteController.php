<?php

namespace App\Http\Controllers;

use App\Http\Requests\DungeonRouteFormRequest;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\UserReport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DungeonRouteController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|null
     */
    public function try(Request $request)
    {
        $result = null;

        if ($request->has('dungeon_id')) {
            $result = view('dungeonroute.try', [
                'headerTitle' => __('Try Keystone.guru'),
                'dungeon_id' => $request->get('dungeon_id'),
                'teeming' => $request->get('teeming')
            ]);
        } else {
            $result = view('dungeonroute.try', ['headerTitle' => __('Try Keystone.guru')]);
        }
        return $result;
    }

    /**
     * Show a page for creating a new dungeon route.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new()
    {
        $result = null;

        $user = Auth::user();
        // @TODO This should be handled differently imho
        if ($user->canCreateDungeonRoute()) {
            $result = view('dungeonroute.new', ['dungeons' => Dungeon::all(), 'headerTitle' => __('New route')]);
        } else {
            $result = view('dungeonroute.limitreached');
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
        $result = null;

        // @TODO This should be handled differently imho
        if (!$dungeonroute->published) {
            $result = view('dungeonroute.unpublished', ['headerTitle' => __('Route unpublished')]);
        } else {
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

            $result = view('dungeonroute.view', [
                'model' => $dungeonroute,
                'current_report' => $currentReport
            ]);
        }

        return $result;
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
            abort(500, __('Unable to save route'));
        }

        return $dungeonroute;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array
     */
    function clone(Request $request, DungeonRoute $dungeonroute)
    {
        $user = Auth::user();

        if ($user->canCreateDungeonRoute()) {
            $relations = [
                $dungeonroute->playerraces,
                $dungeonroute->playerclasses,
                $dungeonroute->affixgroups,
                $dungeonroute->routes,
                $dungeonroute->killzones,
                $dungeonroute->enemyraidmarkers,
                $dungeonroute->mapcomments
            ];

            // @TODO Add a 'clone of' column to DB
            $dungeonroute->id = 0;
            $dungeonroute->exists = false;
            $dungeonroute->title .= sprintf(' (%s)', __('clone'));
            $dungeonroute->public_key = DungeonRoute::generateRandomPublicKey();
            $dungeonroute->published = false;
            $dungeonroute->save();

            foreach ($relations as $relation) {
                foreach ($relation as $model) {
                    /** @var $model Model */
                    $model->id = 0;
                    $model->exists = false;
                    $model->dungeon_route_id = $dungeonroute->id;
                    $model->save();
                }
            }

            if (!Auth::user()->hasPaidTier('unlimited-routes')) {
                \Session::flash('status', sprintf(__('Route cloned. You can create %s more routes.'), $user->getRemainingRouteCount()));
            } else {
                \Session::flash('status', __('Route cloned'));
            }

            return redirect(route('dungeonroute.edit', ['dungeonroute' => $dungeonroute->public_key]));
        } else {
            return view('dungeonroute.limitreached');
        }
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request, DungeonRoute $dungeonroute)
    {
        return view('dungeonroute.edit', ['model' => $dungeonroute, 'headerTitle' => __('Edit route')]);
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
        \Session::flash('status', __('Route created'));

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
