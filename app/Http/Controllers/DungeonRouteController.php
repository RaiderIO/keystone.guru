<?php

namespace App\Http\Controllers;

use App\Http\Requests\DungeonRouteFormRequest;
use App\Logic\MapContext\MapContextDungeonRoute;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Floor;
use App\Models\Npc;
use App\Models\PageView;
use App\Models\UserReport;
use App\Service\Season\SeasonService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Session;
use Teapot\StatusCode;

class DungeonRouteController extends Controller
{
    /**
     * @param Request $request
     * @param SeasonService $seasonService
     * @return Factory|View|null
     */
    public function try(Request $request, SeasonService $seasonService)
    {
        $result = null;

        // User posted
        if ($request->has('dungeon_id')) {
            $dungeonRoute = new DungeonRoute();
            $dungeonRoute->dungeon_id = $request->get('dungeon_id');
            $dungeonRoute->title = sprintf('Trying %s', $dungeonRoute->dungeon->name);
            $dungeonRoute->author_id = -1;
            $dungeonRoute->faction_id = 1; // Unspecified
            $dungeonRoute->public_key = DungeonRoute::generateRandomPublicKey();
            $dungeonRoute->teeming = (int)$request->get('teeming', 0) === 1;
            $dungeonRoute->expires_at = Carbon::now()->addHours(config('keystoneguru.try_dungeon_route_expires_hours'))->toDateTimeString();
            $dungeonRoute->save();

            $result = redirect(route('dungeonroute.edit', ['dungeonroute' => $dungeonRoute]));
        } else if ($request->has('dungeonroute')) {
            // Navigation to /try
            // Only routes that are in try mode
            try {
                $dungeonRoute = DungeonRoute::where('public_key', $request->get('dungeonroute'))
                    ->isTry()->firstOrFail();

                $result = redirect(route('dungeonroute.edit', ['dungeonroute' => $dungeonRoute]));
            } catch (Exception $exception) {
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
     * @return Factory|View
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
     * @return Factory|View
     * @throws AuthorizationException
     */
    public function view(Request $request, DungeonRoute $dungeonroute)
    {
        $defaultFloor = $dungeonroute->dungeon->floors()->where('default', true)->first();
        return $this->viewfloor($request, $dungeonroute, optional($defaultFloor)->index ?? 1);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param int $floorIndex
     * @return Factory|RedirectResponse|View
     * @throws AuthorizationException
     */
    public function viewfloor(Request $request, DungeonRoute $dungeonroute, int $floorIndex)
    {
        $this->authorize('view', $dungeonroute);

        $currentReport = null;
        if (Auth::check()) {
            // Find any currently active report the user has made
            $currentReport = UserReport::where('user_id', Auth::id())
                ->where('model_id', $dungeonroute->id)
                ->where('model_class', get_class($dungeonroute))
                ->where('category', 'dungeonroute')
                ->where('status', 0)
                ->first();
        }

        PageView::trackPageView($dungeonroute->id, get_class($dungeonroute));
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorIndex)->first();

        if ($floor === null) {
            return redirect()->route('dungeonroute.view', ['dungeonroute' => $dungeonroute->public_key]);
        } else {
            return view('dungeonroute.view', [
                'model'          => $dungeonroute,
                'current_report' => $currentReport,
                'floor'          => $floor,
                'npcs'           => Npc::all()->whereIn('dungeon_id', [$floor->dungeon_id, -1])->map(function ($npc)
                {
                    return ['id' => $npc->id, 'name' => $npc->name, 'dungeon_id' => $npc->dungeon_id];
                }),
            ]);
        }
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param int $floorindex
     * @return Factory|View
     */
    public function preview(Request $request, DungeonRoute $dungeonroute, int $floorindex)
    {
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorindex)->first();
        return view('dungeonroute.preview', [
            'model'   => $dungeonroute,
            'floorId' => $floor->id,
            'mapContext' => new MapContextDungeonRoute($dungeonroute, $floor)
        ]);
    }

    /**
     * @param DungeonRouteFormRequest $request
     * @param SeasonService $seasonService
     * @param DungeonRoute|null $dungeonroute
     * @return mixed
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
     * @return Application|RedirectResponse|Redirector
     * @throws AuthorizationException
     */
    function clone(Request $request, DungeonRoute $dungeonroute)
    {
        $this->authorize('clone', $dungeonroute);

        $user = Auth::user();

        if ($user->canCreateDungeonRoute()) {

            $newRoute = $dungeonroute->clone();

            if (!Auth::user()->hasPaidTier('unlimited-routes')) {
                Session::flash('status', sprintf(__('Route cloned. You can create %s more routes.'), $user->getRemainingRouteCount()));
            } else {
                Session::flash('status', __('Route cloned'));
            }

            return redirect(route('dungeonroute.edit', ['dungeonroute' => $newRoute->public_key]));
        } else {
            return view('dungeonroute.limitreached');
        }
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return RedirectResponse
     */
    public function claim(Request $request, DungeonRoute $dungeonroute)
    {
        if ($dungeonroute->isTry()) {
            $dungeonroute->claim(Auth::id());
        }
        return redirect()->route('dungeonroute.edit', ['dungeonroute' => $dungeonroute->public_key]);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return Factory|View
     * @throws AuthorizationException
     */
    public function edit(Request $request, DungeonRoute $dungeonroute)
    {
        $defaultFloor = $dungeonroute->dungeon->floors()->where('default', true)->first();
        return $this->editfloor($request, $dungeonroute, optional($defaultFloor)->index ?? 1);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param int $floorIndex
     * @return Factory|RedirectResponse|View
     * @throws AuthorizationException
     */
    public function editfloor(Request $request, DungeonRoute $dungeonroute, int $floorIndex)
    {
        $this->authorize('edit', $dungeonroute);

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorIndex)->first();

        if ($floor === null) {
            return redirect()->route('dungeonroute.edit', ['dungeonroute' => $dungeonroute->public_key]);
        } else {
            if ($dungeonroute->isTry()) {
                return view('dungeonroute.try', [
                    'model'      => $dungeonroute,
                    'floor'      => $floor,
                    'mapContext' => (new MapContextDungeonRoute($dungeonroute, $floor))
                ]);
            } else {
                return view('dungeonroute.edit', [
                    'headerTitle' => __('Edit route'),
                    'model'       => $dungeonroute,
                    'floor'       => $floor,
                    'mapContext'  => (new MapContextDungeonRoute($dungeonroute, $floor))
                ]);
            }
        }
    }


    /**
     * Override to give the type hint which is required.
     *
     * @param DungeonRouteFormRequest $request
     * @param SeasonService $seasonService
     * @param DungeonRoute $dungeonroute
     * @return Factory|View
     * @throws Exception
     */
    public function update(DungeonRouteFormRequest $request, SeasonService $seasonService, DungeonRoute $dungeonroute)
    {
        $this->authorize('edit', $dungeonroute);

        // Store it and show the edit page again
        $dungeonroute = $this->store($request, $seasonService, $dungeonroute);

        // Message to the user
        Session::flash('status', __('Dungeonroute updated'));

        // Display the edit page
        return $this->edit($request, $dungeonroute);
    }

    /**
     * @param DungeonRouteFormRequest $request
     * @param SeasonService $seasonService
     * @return RedirectResponse
     * @throws Exception
     */
    public function savenew(DungeonRouteFormRequest $request, SeasonService $seasonService)
    {
        // Store it and show the edit page
        $dungeonroute = $this->store($request, $seasonService);

        // Message to the user
        Session::flash('status', __('Route created'));

        return redirect()->route('dungeonroute.edit', ['dungeonroute' => $dungeonroute]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return Factory|
     */
    public function list()
    {
        return view('dungeonroute.list', ['models' => DungeonRoute::all()]);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return RedirectResponse|Redirector
     */
    public function editLegacy(Request $request, DungeonRoute $dungeonroute)
    {
        return redirect(route('dungeonroute.edit', ['dungeonroute' => $dungeonroute->public_key]), StatusCode::MOVED_PERMANENTLY);
    }

    /**
     * @param DungeonRouteFormRequest $request
     * @param DungeonRoute $dungeonroute
     * @return RedirectResponse|Redirector
     */
    public function updateLegacy(DungeonRouteFormRequest $request, DungeonRoute $dungeonroute)
    {
        return redirect(route('dungeonroute.update', ['dungeonroute' => $dungeonroute->public_key]), StatusCode::MOVED_PERMANENTLY);
    }
}
