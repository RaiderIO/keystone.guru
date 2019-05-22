<?php

namespace App\Http\Controllers;

use App\Http\Requests\DungeonRouteFormRequest;
use App\Models\Brushline;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Floor;
use App\Models\KillZone;
use App\Models\PageView;
use App\Models\UserReport;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        // User posted
        if ($request->has('dungeon_id')) {
            $dungeonRoute = new DungeonRoute();
            $dungeonRoute->dungeon_id = $request->get('dungeon_id');
            $dungeonRoute->author_id = Auth::check() ? Auth::id() : -1;
            $dungeonRoute->faction_id = 1; // Unspecified
            $dungeonRoute->public_key = DungeonRoute::generateRandomPublicKey();
            $dungeonRoute->teeming = (int)$request->get('teeming', 0) === 1;
            $dungeonRoute->expires_at = Carbon::now()->addHour(config('keystoneguru.try_dungeon_route_expires_hours'))->toDateTimeString();
            $dungeonRoute->save();

            $result = view('dungeonroute.try', ['model' => $dungeonRoute]);
        } else if ($request->has('dungeonroute')) {
            // Navigation to /try
            // Only routes that are in try mode
            try {
                $dungeonRoute = DungeonRoute::where('public_key', $request->get('dungeonroute'))
                    ->isTry()->firstOrFail();

                $result = view('dungeonroute.try', ['model' => $dungeonRoute]);
            } catch (\Exception $exception) {
                $result = view('dungeonroute.tryclaimed');
            }
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
     * @throws AuthorizationException
     */
    public function view(Request $request, DungeonRoute $dungeonroute)
    {
        $this->authorize('view', $dungeonroute);

        $currentReport = null;
        if (Auth::check()) {
            // Find any currently active report the user has made
            $currentReport = UserReport::where('author_id', Auth::id())
                ->where('context', $dungeonroute->getReportContext())
                ->where('category', 'dungeonroute')
                ->where('handled', 0)
                ->first();
        }

        PageView::trackPageView($dungeonroute->id, get_class($dungeonroute));

        return view('dungeonroute.view', [
            'model' => $dungeonroute,
            'current_report' => $currentReport
        ]);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param int $floorindex
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function preview(Request $request, DungeonRoute $dungeonroute, int $floorindex)
    {
        $result = view('dungeonroute.preview', [
            'model' => $dungeonroute,
            'floorId' => Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorindex)->first()->id
        ]);

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
     * @throws AuthorizationException
     */
    function clone(Request $request, DungeonRoute $dungeonroute)
    {
        $this->authorize('clone', $dungeonroute);

        $user = Auth::user();

        if ($user->canCreateDungeonRoute()) {
            $relations = [
                $dungeonroute->playerraces,
                $dungeonroute->playerclasses,
                $dungeonroute->affixgroups,
                $dungeonroute->paths,
                $dungeonroute->brushlines,
                $dungeonroute->killzones,
                $dungeonroute->enemyraidmarkers,
                $dungeonroute->mapcomments,
                $dungeonroute->routeattributesraw
            ];

            $dungeonroute->id = 0;
            $dungeonroute->exists = false;
            $dungeonroute->author_id = Auth::user()->id;
            $dungeonroute->title .= sprintf(' (%s)', __('clone'));
            $dungeonroute->clone_of = $dungeonroute->public_key;
            $dungeonroute->public_key = DungeonRoute::generateRandomPublicKey();
            $dungeonroute->published = false;
            $dungeonroute->save();

            // Link all relations to their new dungeon route
            foreach ($relations as $relation) {
                foreach ($relation as $model) {
                    /** @var $model Model */
                    $model->id = 0;
                    $model->exists = false;
                    $model->dungeon_route_id = $dungeonroute->id;
                    $model->save();

                    // KillZone, save the enemies that were attached to them
                    if ($model instanceof KillZone) {
                        foreach ($model->killzoneenemies as $enemy) {
                            $enemy->id = 0;
                            $enemy->exists = false;
                            $enemy->kill_zone_id = $model->id;
                            $enemy->save();
                        }
                    } // Make sure all polylines are copied over
                    else if (isset($model->polyline_id)) {
                        // It's not technically a brushline, but all other polyline using structs have the same auto complete
                        // Save a new polyline
                        /** @var Brushline $model */
                        $model->polyline->id = 0;
                        $model->polyline->exists = false;
                        $model->polyline->model_id = $model->id;
                        $model->polyline->save();

                        // Write the polyline back to the model
                        $model->polyline_id = $model->polyline->id;
                    }
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
     * @throws AuthorizationException
     */
    public function edit(Request $request, DungeonRoute $dungeonroute)
    {
        $this->authorize('edit', $dungeonroute);

        // Make sure the dungeon route is owned by this user if it was in try mode.
        // Don't share your try routes if you don't want someone else to claim the route!
        $dungeonroute->claim(Auth::user());

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
        $this->authorize('edit', $dungeonroute);

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
