<?php

namespace App\Http\Controllers;

use App\Http\Requests\DungeonRoute\DungeonRouteFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteTemporaryFormRequest;
use App\Logic\MapContext\MapContextDungeonRoute;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Floor;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Session;
use Teapot\StatusCode;

class DungeonRouteController extends Controller
{

    /**
     * @return Factory|View
     */
    public function new()
    {
        return view('dungeonroute.new');
    }

    /**
     * @return Factory|View
     */
    public function newtemporary()
    {
        return view('dungeonroute.newtemporary', ['dungeons' => Dungeon::all(), 'headerTitle' => __('New temporary route')]);
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
        return $this->viewfloor($request, $dungeonroute, optional($defaultFloor)->index ?? '1');
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param string $floorIndex
     * @return Factory|RedirectResponse|View
     * @throws AuthorizationException
     */
    public function viewfloor(Request $request, DungeonRoute $dungeonroute, string $floorIndex)
    {
        $this->authorize('view', $dungeonroute);

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

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

        // Handle route views counting
        if (PageView::trackPageView($dungeonroute->id, get_class($dungeonroute))) {
            // Do not update the updated_at time - triggering a refresh of the thumbnails
            $dungeonroute->timestamps = false;
            $dungeonroute->views++;
            $dungeonroute->popularity++;
            $dungeonroute->update(['views', 'popularity']);
        }

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorIndex)->first();

        if ($floor === null) {
            return redirect()->route('dungeonroute.view', ['dungeonroute' => $dungeonroute->public_key]);
        } else {
            return view('dungeonroute.view', [
                'dungeonroute'   => $dungeonroute,
                'current_report' => $currentReport,
                'floor'          => $floor,
                'mapContext'     => (new MapContextDungeonRoute($dungeonroute, $floor))->toArray()
            ]);
        }
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param string $floorindex
     * @return Factory|View
     */
    public function preview(Request $request, DungeonRoute $dungeonroute, string $floorindex)
    {
        $this->authorize('preview', [$dungeonroute, $request->get('secret', '')]);

        if (!is_numeric($floorindex)) {
            $floorindex = '1';
        }

        /** @var FLoor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorindex)->first();
        return view('dungeonroute.preview', [
            'dungeonroute' => $dungeonroute,
            'floorId'      => $floor->id,
            'mapContext'   => (new MapContextDungeonRoute($dungeonroute, $floor))->toArray()
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
     * @param DungeonRouteTemporaryFormRequest $request
     * @param SeasonService $seasonService
     * @return mixed
     */
    public function storetemporary(DungeonRouteTemporaryFormRequest $request, SeasonService $seasonService)
    {
        $dungeonroute = new DungeonRoute();

        // May fail
        if (!$dungeonroute->saveTemporaryFromRequest($request, $seasonService)) {
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

            $newRoute = $dungeonroute->cloneRoute();

//            if (!Auth::user()->hasPaidTier('unlimited-routes')) {
//                Session::flash('status', sprintf(__('Route cloned. You can create %s more routes.'), $user->getRemainingRouteCount()));
//            } else {
            Session::flash('status', __('Route cloned successfully'));
//            }

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
        // Regardless of the result, try to claim the route
        $dungeonroute->claim(Auth::id());

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
        /** @var Floor $defaultFloor */
        $defaultFloor = $dungeonroute->dungeon->floors()->where('default', true)->first();
        return $this->editfloor($request, $dungeonroute, optional($defaultFloor)->index ?? '1');
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param string $floorIndex
     * @return Factory|RedirectResponse|View
     * @throws AuthorizationException
     */
    public function editfloor(Request $request, DungeonRoute $dungeonroute, string $floorIndex)
    {
        $this->authorize('edit', $dungeonroute);

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorIndex)->first();

        if ($floor === null) {
            return redirect()->route('dungeonroute.edit', ['dungeonroute' => $dungeonroute->public_key]);
        } else {
            return view('dungeonroute.edit', [
                'headerTitle'  => __('Edit route'),
                'dungeonroute' => $dungeonroute,
                'floor'        => $floor,
                'mapContext'   => (new MapContextDungeonRoute($dungeonroute, $floor))->toArray()
            ]);
        }
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param string $floorIndex
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function embed(Request $request, DungeonRoute $dungeonroute, string $floorIndex = '1')
    {
        $this->authorize('embed', $dungeonroute);

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorIndex)->first();

        return view('dungeonroute.embed', [
            'dungeonroute' => $dungeonroute,
            'floor'        => $floor,
            'mapContext'   => (new MapContextDungeonRoute($dungeonroute, $floor))->toArray()
        ]);
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
     * @param DungeonRouteTemporaryFormRequest $request
     * @param SeasonService $seasonService
     * @return RedirectResponse
     * @throws Exception
     */
    public function savenewtemporary(DungeonRouteTemporaryFormRequest $request, SeasonService $seasonService)
    {
        // Store it and show the edit page
        $dungeonroute = $this->storetemporary($request, $seasonService);

        // Message to the user
        Session::flash('status', __('Route created'));

        return redirect()->route('dungeonroute.edit', ['dungeonroute' => $dungeonroute]);
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
