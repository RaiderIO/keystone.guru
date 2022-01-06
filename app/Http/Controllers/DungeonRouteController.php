<?php

namespace App\Http\Controllers;

use App\Http\Requests\DungeonRoute\DungeonRouteFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteTemporaryFormRequest;
use App\Http\Requests\DungeonRoute\EmbedFormRequest;
use App\Logic\MapContext\MapContextDungeonRoute;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Floor;
use App\Models\PageView;
use App\Models\UserReport;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Psr\SimpleCache\InvalidArgumentException;
use Session;

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
        return view('dungeonroute.newtemporary', ['dungeons' => Dungeon::all()]);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return Factory|View
     * @throws AuthorizationException
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
                'mapContext'     => (new MapContextDungeonRoute($dungeonroute, $floor))->getProperties(),
            ]);
        }
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param string $floorindex
     * @return Factory|View
     * @throws AuthorizationException
     * @throws InvalidArgumentException
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
            'mapContext'   => (new MapContextDungeonRoute($dungeonroute, $floor))->getProperties(),
        ]);
    }

    /**
     * @param DungeonRouteFormRequest $request
     * @param SeasonServiceInterface $seasonService
     * @param DungeonRoute|null $dungeonroute
     * @return DungeonRoute
     * @throws Exception
     */
    public function store(DungeonRouteFormRequest $request, SeasonServiceInterface $seasonService, DungeonRoute $dungeonroute = null): DungeonRoute
    {
        if ($dungeonroute === null) {
            $dungeonroute = new DungeonRoute();
        }

        // May fail
        if (!$dungeonroute->saveFromRequest($request, $seasonService)) {
            abort(500, __('controller.dungeonroute.unable_to_save'));
        }

        return $dungeonroute;
    }

    /**
     * @param DungeonRouteTemporaryFormRequest $request
     * @param SeasonServiceInterface $seasonService
     * @return DungeonRoute
     * @throws Exception
     */
    public function storetemporary(DungeonRouteTemporaryFormRequest $request, SeasonServiceInterface $seasonService): DungeonRoute
    {
        $dungeonroute = new DungeonRoute();

        // May fail
        if (!$dungeonroute->saveTemporaryFromRequest($request, $seasonService)) {
            abort(500, __('controller.dungeonroute.unable_to_save'));
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

            Session::flash('status', __('controller.dungeonroute.flash.route_cloned_successfully'));

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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
                'dungeonroute' => $dungeonroute,
                'floor'        => $floor,
                'mapContext'   => (new MapContextDungeonRoute($dungeonroute, $floor))->getProperties(),
            ]);
        }
    }

    /**
     * @param EmbedFormRequest $request
     * @param DungeonRoute $dungeonroute
     * @param string $floorIndex
     * @return Application|Factory|View
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function embed(EmbedFormRequest $request, DungeonRoute $dungeonroute, string $floorIndex = '1')
    {
        $this->authorize('embed', $dungeonroute);

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorIndex)->first();

        $pulls             = $request->get('pulls');
        $pullsDefaultState = $request->get('pullsDefaultState');
        $pullsHideOnMove   = $request->get('pullsHideOnMove');
        $enemyinfo         = $request->get('enemyinfo');

        return view('dungeonroute.embed', [
            'dungeonroute' => $dungeonroute,
            'floor'        => $floor,
            'mapContext'   => (new MapContextDungeonRoute($dungeonroute, $floor))->getProperties(),
            'embedOptions' => [
                // Null if not set - but cast to an bool if it is ("0" or 0 both equal false, "1" or 1 both equal true
                'pulls'             => $pulls === null || $pulls, // Default true - available
                'pullsDefaultState' => (bool)$pullsDefaultState, // Default false - closed
                'pullsHideOnMove'   => $pullsHideOnMove === null ? null : (bool)$pullsHideOnMove,
                'enemyinfo'         => (bool)$enemyinfo, // Default false - not available
            ],
        ]);
    }


    /**
     * Override to give the type hint which is required.
     *
     * @param DungeonRouteFormRequest $request
     * @param SeasonServiceInterface $seasonService
     * @param DungeonRoute $dungeonroute
     * @return Factory|View
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function update(DungeonRouteFormRequest $request, SeasonServiceInterface $seasonService, DungeonRoute $dungeonroute)
    {
        $this->authorize('edit', $dungeonroute);

        // Store it and show the edit page again
        $dungeonroute = $this->store($request, $seasonService);

        // Message to the user
        Session::flash('status', __('controller.dungeonroute.flash.route_updated'));

        // Display the edit page
        return $this->edit($request, $dungeonroute);
    }

    /**
     * @param DungeonRouteFormRequest $request
     * @param SeasonServiceInterface $seasonService
     * @return RedirectResponse
     * @throws Exception
     */
    public function savenew(DungeonRouteFormRequest $request, SeasonServiceInterface $seasonService)
    {
        // Store it and show the edit page
        $dungeonroute = $this->store($request, $seasonService);

        // Message to the user
        Session::flash('status', __('controller.dungeonroute.flash.route_created'));

        return redirect()->route('dungeonroute.edit', ['dungeonroute' => $dungeonroute]);
    }

    /**
     * @param DungeonRouteTemporaryFormRequest $request
     * @param SeasonServiceInterface $seasonService
     * @return RedirectResponse
     * @throws Exception
     */
    public function savenewtemporary(DungeonRouteTemporaryFormRequest $request, SeasonServiceInterface $seasonService)
    {
        // Store it and show the edit page
        $dungeonroute = $this->storetemporary($request, $seasonService);

        // Message to the user
        Session::flash('status', __('controller.dungeonroute.flash.route_created'));

        return redirect()->route('dungeonroute.edit', ['dungeonroute' => $dungeonroute]);
    }
}
