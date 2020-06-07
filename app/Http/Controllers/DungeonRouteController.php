<?php

namespace App\Http\Controllers;

use App\Http\Requests\DungeonRouteFormRequest;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Floor;
use App\Models\PageView;
use App\Models\UserReport;
use App\Service\Season\SeasonService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DungeonRouteController extends Controller
{
    /**
     * @param Request $request
     * @param SeasonService $seasonService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|null
     */
    public function try(Request $request, SeasonService $seasonService)
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
        return $this->viewfloor($request, $dungeonroute, 1);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param int $floorIndex
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     * @throws AuthorizationException
     */
    public function viewfloor(Request $request, DungeonRoute $dungeonroute, int $floorIndex)
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
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorIndex)->first();

        if ($floor === null) {
            return redirect()->route('dungeonroute.view', ['dungeonroute' => $dungeonroute->public_key]);
        } else {
            return view('dungeonroute.view', [
                'model'          => $dungeonroute,
                'current_report' => $currentReport,
                'floor'          => $floor
            ]);
        }
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
            'model'   => $dungeonroute,
            'floorId' => Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorindex)->first()->id
        ]);

        return $result;
    }

    /**
     * @param DungeonRouteFormRequest $request
     * @param SeasonService $seasonService
     * @param DungeonRoute $dungeonroute
     * @return mixed
     * @throws \Exception
     */
    public function store(DungeonRouteFormRequest $request, SeasonService $seasonService, DungeonRoute $dungeonroute = null)
    {
        if ($dungeonroute === null) {
            $dungeonroute = new DungeonRoute();
        }

        // May fail
        if (!$dungeonroute->saveFromRequest($request, $seasonService)) {
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

            $newRoute = $dungeonroute->clone();

            if (!Auth::user()->hasPaidTier('unlimited-routes')) {
                \Session::flash('status', sprintf(__('Route cloned. You can create %s more routes.'), $user->getRemainingRouteCount()));
            } else {
                \Session::flash('status', __('Route cloned'));
            }

            return redirect(route('dungeonroute.edit', ['dungeonroute' => $newRoute->public_key]));
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
        return $this->editfloor($request, $dungeonroute, 1);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param int $floorIndex
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     * @throws AuthorizationException
     */
    public function editfloor(Request $request, DungeonRoute $dungeonroute, int $floorIndex)
    {
        $this->authorize('edit', $dungeonroute);

        // Make sure the dungeon route is owned by this user if it was in try mode.
        // Don't share your try routes if you don't want someone else to claim the route!
        $dungeonroute->claim(Auth::user());

        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorIndex)->first();

        if ($floor === null) {
            return redirect()->route('dungeonroute.edit', ['dungeonroute' => $dungeonroute->public_key]);
        } else {
            return view('dungeonroute.edit', [
                'headerTitle' => __('Edit route'),
                'model'       => $dungeonroute,
                'floor'       => $floor
            ]);
        }
    }


    /**
     * Override to give the type hint which is required.
     *
     * @param DungeonRouteFormRequest $request
     * @param SeasonService $seasonService
     * @param DungeonRoute $dungeonroute
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function update(DungeonRouteFormRequest $request, SeasonService $seasonService, DungeonRoute $dungeonroute)
    {
        $this->authorize('edit', $dungeonroute);

        // Store it and show the edit page again
        $dungeonroute = $this->store($request, $seasonService, $dungeonroute);

        // Message to the user
        \Session::flash('status', __('Dungeonroute updated'));

        // Display the edit page
        return $this->edit($request, $dungeonroute);
    }

    /**
     * @param DungeonRouteFormRequest $request
     * @param SeasonService $seasonService
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function savenew(DungeonRouteFormRequest $request, SeasonService $seasonService)
    {
        // Store it and show the edit page
        $dungeonroute = $this->store($request, $seasonService);

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

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function editLegacy(Request $request, DungeonRoute $dungeonroute)
    {
        return redirect(route('dungeonroute.edit', ['dungeonroute' => $dungeonroute->public_key]), 301);
    }

    /**
     * @param DungeonRouteFormRequest $request
     * @param DungeonRoute $dungeonroute
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function updateLegacy(DungeonRouteFormRequest $request, DungeonRoute $dungeonroute)
    {
        return redirect(route('dungeonroute.update', ['dungeonroute' => $dungeonroute->public_key]), 301);
    }
}
