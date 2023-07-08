<?php

namespace App\Http\Controllers;

use App\Http\Requests\DungeonRoute\DungeonRouteFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteTemporaryFormRequest;
use App\Http\Requests\DungeonRoute\EmbedFormRequest;
use App\Http\Requests\DungeonRoute\MigrateToSeasonalTypeRequest;
use App\Jobs\RefreshEnemyForces;
use App\Logic\MapContext\MapContextDungeonRoute;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Floor;
use App\Models\UserReport;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Carbon\Carbon;
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
use Teapot\StatusCode\Http;

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
     * @param Request      $request
     * @param Dungeon      $dungeon
     * @param DungeonRoute $dungeonroute
     * @param string|null  $title
     * @return RedirectResponse
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function view(Request $request, Dungeon $dungeon, DungeonRoute $dungeonroute, ?string $title = null): RedirectResponse
    {
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('default', true)->first();

        return redirect()->route('dungeonroute.view.floor', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'floorindex'   => optional($defaultFloor)->index ?? '1',
        ]);
    }

    /**
     * @param Request      $request
     * @param Dungeon      $dungeon
     * @param DungeonRoute $dungeonroute
     * @param string       $title
     * @param string       $floorIndex
     * @return Factory|RedirectResponse|View
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function viewfloor(Request $request, Dungeon $dungeon, DungeonRoute $dungeonroute, string $title, string $floorIndex)
    {
        $this->authorize('view', $dungeonroute);

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        if (!isset($title) || $dungeonroute->getTitleSlug() !== $title) {
            return redirect()->route('dungeonroute.view', [
                'dungeon'      => $dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
            ]);
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

        $dungeonroute->trackPageView(DungeonRoute::PAGE_VIEW_SOURCE_VIEW_ROUTE);

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorIndex)->first();

        if ($floor === null) {
            /** @var Floor $defaultFloor */
            $defaultFloor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('default', true)->first();

            return redirect()->route('dungeonroute.view.floor', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
                'floorindex'   => optional($defaultFloor)->index ?? '1',
            ]);
        } else {
            return view('dungeonroute.view', [
                'dungeon'        => $dungeonroute->dungeon,
                'dungeonroute'   => $dungeonroute,
                'title'          => $dungeonroute->getTitleSlug(),
                'current_report' => $currentReport,
                'floor'          => $floor,
                'mapContext'     => (new MapContextDungeonRoute($dungeonroute, $floor))->getProperties(),
            ]);
        }
    }

    /**
     * @param Request      $request
     * @param Dungeon      $dungeon
     * @param DungeonRoute $dungeonroute
     * @param string       $title
     * @param string       $floorIndex
     * @return Factory|RedirectResponse|View
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function preview(Request $request, Dungeon $dungeon, DungeonRoute $dungeonroute, string $title, string $floorIndex)
    {
        $this->authorize('preview', [$dungeonroute, $request->get('secret', '') ?? '']);

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        $titleSlug = $dungeonroute->getTitleSlug();
        if (!isset($title) || $titleSlug !== $title) {
            return redirect()->route('dungeonroute.preview', [
                'dungeon'      => $dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $titleSlug,
                'floorindex'   => $floorIndex,
            ]);
        }

        /** @var FLoor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorIndex)->first();
        return view('dungeonroute.preview', [
            'dungeonroute' => $dungeonroute,
            'floorId'      => $floor->id,
            'mapContext'   => (new MapContextDungeonRoute($dungeonroute, $floor))->getProperties(),
        ]);
    }

    /**
     * @param ExpansionServiceInterface    $expansionService
     * @param MigrateToSeasonalTypeRequest $request
     * @param Dungeon                      $dungeon
     * @param DungeonRoute                 $dungeonroute
     * @param string                       $title
     * @param string                       $seasonalType
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function migrateToSeasonalType(
        ExpansionServiceInterface    $expansionService,
        MigrateToSeasonalTypeRequest $request,
        Dungeon                      $dungeon,
        DungeonRoute                 $dungeonroute,
        string                       $title,
        string                       $seasonalType)
    {
        $this->authorize('migrate', $dungeonroute);

        $dungeonroute->migrateToSeasonalType($expansionService, $seasonalType);

        return redirect()->route('dungeonroute.edit', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $title]);
    }

    /**
     * @param DungeonRouteFormRequest   $request
     * @param SeasonServiceInterface    $seasonService
     * @param ExpansionServiceInterface $expansionService
     * @param ThumbnailServiceInterface $thumbnailService
     * @param DungeonRoute|null         $dungeonroute
     * @return DungeonRoute
     * @throws Exception
     */
    public function store(DungeonRouteFormRequest $request, SeasonServiceInterface $seasonService, ExpansionServiceInterface $expansionService, ThumbnailServiceInterface $thumbnailService, DungeonRoute $dungeonroute = null): DungeonRoute
    {
        if ($dungeonroute === null) {
            $dungeonroute = new DungeonRoute();
        }

        // May fail
        if (!$dungeonroute->saveFromRequest($request, $seasonService, $expansionService, $thumbnailService)) {
            abort(500, __('controller.dungeonroute.unable_to_save'));
        }

        return $dungeonroute;
    }

    /**
     * @param DungeonRouteTemporaryFormRequest $request
     * @param SeasonServiceInterface           $seasonService
     * @param ExpansionServiceInterface        $expansionService
     * @return DungeonRoute
     * @throws Exception
     */
    public function storetemporary(DungeonRouteTemporaryFormRequest $request, SeasonServiceInterface $seasonService, ExpansionServiceInterface $expansionService): DungeonRoute
    {
        $dungeonroute = new DungeonRoute();

        // May fail
        if (!$dungeonroute->saveTemporaryFromRequest($request, $seasonService, $expansionService)) {
            abort(500, __('controller.dungeonroute.unable_to_save'));
        }

        return $dungeonroute;
    }

    /**
     * @param Request                   $request
     * @param Dungeon                   $dungeon
     * @param DungeonRoute              $dungeonroute
     * @param string                    $title
     * @param ThumbnailServiceInterface $thumbnailService
     * @return Application|RedirectResponse|Redirector
     * @throws AuthorizationException
     */
    function clone(Request $request, Dungeon $dungeon, DungeonRoute $dungeonroute, string $title, ThumbnailServiceInterface $thumbnailService)
    {
        $this->authorize('clone', $dungeonroute);

        $user = Auth::user();

        if ($user->canCreateDungeonRoute()) {
            $newRoute = $dungeonroute->cloneRoute($thumbnailService);

            Session::flash('status', __('controller.dungeonroute.flash.route_cloned_successfully'));

            return redirect()->route('dungeonroute.edit', [
                'dungeon'      => $newRoute->dungeon,
                'dungeonroute' => $newRoute,
                'title'        => $newRoute->title,
            ]);
        } else {
            return view('dungeonroute.limitreached');
        }
    }

    /**
     * @param Request      $request
     * @param Dungeon      $dungeon
     * @param DungeonRoute $dungeonroute
     * @param string       $title
     * @return RedirectResponse
     */
    public function claim(Request $request, Dungeon $dungeon, DungeonRoute $dungeonroute, string $title)
    {
        // Regardless of the result, try to claim the route
        $dungeonroute->claim(Auth::id());

        return redirect()->route('dungeonroute.edit', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
        ]);
    }

    /**
     * @param Request      $request
     * @param Dungeon      $dungeon
     * @param DungeonRoute $dungeonroute
     * @param string|null  $title
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws InvalidArgumentException
     */
    public function edit(Request $request, Dungeon $dungeon, DungeonRoute $dungeonroute, ?string $title = null): RedirectResponse
    {
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('default', true)->first();

        return redirect()->route('dungeonroute.edit.floor', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'floorindex'   => optional($defaultFloor)->index ?? '1',
        ]);
    }

    /**
     * @param Request      $request
     * @param Dungeon      $dungeon
     * @param DungeonRoute $dungeonroute
     * @param string|null  $title
     * @param string|null  $floorIndex
     * @return Factory|RedirectResponse|View
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function editfloor(Request $request, Dungeon $dungeon, DungeonRoute $dungeonroute, ?string $title, ?string $floorIndex)
    {
        $this->authorize('edit', $dungeonroute);

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        $titleSlug = $dungeonroute->getTitleSlug();
        if (!isset($title) || $titleSlug !== $title) {
            return redirect()->route('dungeonroute.edit.floor', [
                'dungeon'      => $dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $titleSlug,
                'floorindex'   => $floorIndex,
            ]);
        }

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('index', $floorIndex)->first();

        if ($floor === null) {
            /** @var Floor $defaultFloor */
            $defaultFloor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)->where('default', true)->first();

            return redirect()->route('dungeonroute.edit.floor', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
                'floorindex'   => optional($defaultFloor)->index ?? '1',
            ]);
        } else {
            return view('dungeonroute.edit', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
                'floor'        => $floor,
                'mapContext'   => (new MapContextDungeonRoute($dungeonroute, $floor))->getProperties(),
                'floorindex'   => $floorIndex,
            ]);
        }
    }

    /**
     * @param EmbedFormRequest $request
     * @param DungeonRoute     $dungeonroute
     * @param string           $floorIndex
     * @return Application|Factory|View
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function embed(EmbedFormRequest $request, $dungeonroute, string $floorIndex = '1')
    {
        if (!is_numeric($floorIndex)) {
            $dungeonroute = DungeonRoute::where('public_key', $floorIndex)->first();
            if ($dungeonroute === null) {
                return response('Not found', Http::NOT_FOUND);
            }
        }
        $this->authorize('embed', $dungeonroute);

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        $dungeonroute->trackPageView(DungeonRoute::PAGE_VIEW_SOURCE_VIEW_EMBED);

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)
            ->where('index', $floorIndex)
            ->first();

        $style                 = $request->get('style', 'regular');
        $pullsDefaultState     = $request->get('pullsDefaultState');
        $pullsHideOnMove       = $request->get('pullsHideOnMove');
        $headerBackgroundColor = $request->get('headerBackgroundColor');
        $mapBackgroundColor    = $request->get('mapBackgroundColor');

        $showEnemyInfo   = $request->get('showEnemyInfo', false);
        $showPulls       = $request->get('showPulls', true);
        $showEnemyForces = $request->get('showEnemyForces', true);
        $showAffixes     = $request->get('showAffixes', true);
        $showTitle       = $request->get('showTitle', true);

        return view('dungeonroute.embed', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'floor'        => $floor,
            'mapContext'   => (new MapContextDungeonRoute($dungeonroute, $floor))->getProperties(),
            'embedOptions' => [
                'style'                 => $style,
                // Null if not set - but cast to an bool if it is ("0" or 0 both equal false, "1" or 1 both equal true
                'pullsDefaultState'     => (int)$pullsDefaultState, // Default false - closed
                'pullsHideOnMove'       => $pullsHideOnMove === null ? null : (bool)$pullsHideOnMove,
                'headerBackgroundColor' => $headerBackgroundColor,
                'mapBackgroundColor'    => $mapBackgroundColor,
                'show'                  => [
                    'enemyInfo'   => (bool)$showEnemyInfo, // Default false - not available
                    'pulls'       => (bool)$showPulls, // Default true - available
                    'enemyForces' => (bool)$showEnemyForces, // Default true - available
                    'affixes'     => (bool)$showAffixes, // Default true - available
                    'title'       => (bool)$showTitle, // Default true - available
                ],
            ],
        ]);
    }


    /**
     * Override to give the type hint which is required.
     *
     * @param DungeonRouteFormRequest   $request
     * @param SeasonServiceInterface    $seasonService
     * @param ExpansionServiceInterface $expansionService
     * @param ThumbnailServiceInterface $thumbnailService
     * @param DungeonRoute              $dungeonroute
     * @return \Illuminate\Http\RedirectResponse
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function update(DungeonRouteFormRequest $request, SeasonServiceInterface $seasonService, ExpansionServiceInterface $expansionService, ThumbnailServiceInterface $thumbnailService, DungeonRoute $dungeonroute): RedirectResponse
    {
        $this->authorize('edit', $dungeonroute);

        // Store it and show the edit page again
        $dungeonroute = $this->store($request, $seasonService, $expansionService, $thumbnailService);

        // Message to the user
        Session::flash('status', __('controller.dungeonroute.flash.route_updated'));

        // Display the edit page
        return $this->edit($request, $dungeonroute->dungeon, $dungeonroute, $dungeonroute->getTitleSlug());
    }

    /**
     * @param DungeonRouteFormRequest   $request
     * @param SeasonServiceInterface    $seasonService
     * @param ExpansionServiceInterface $expansionService
     * @param ThumbnailServiceInterface $thumbnailService
     * @return RedirectResponse
     * @throws Exception
     */
    public function savenew(DungeonRouteFormRequest $request, SeasonServiceInterface $seasonService, ExpansionServiceInterface $expansionService, ThumbnailServiceInterface $thumbnailService)
    {
        // Store it and show the edit page
        $dungeonroute = $this->store($request, $seasonService, $expansionService, $thumbnailService);

        // Message to the user
        Session::flash('status', __('controller.dungeonroute.flash.route_created'));

        return redirect()->route('dungeonroute.edit', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
        ]);
    }

    /**
     * @param DungeonRouteTemporaryFormRequest $request
     * @param SeasonServiceInterface           $seasonService
     * @param ExpansionServiceInterface        $expansionService
     * @return RedirectResponse
     * @throws Exception
     */
    public function savenewtemporary(DungeonRouteTemporaryFormRequest $request, SeasonServiceInterface $seasonService, ExpansionServiceInterface $expansionService)
    {
        // Store it and show the edit page
        $dungeonroute = $this->storetemporary($request, $seasonService, $expansionService);

        // Message to the user
        Session::flash('status', __('controller.dungeonroute.flash.route_created'));

        return redirect()->route('dungeonroute.edit', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
        ]);
    }

    /**
     * @param DungeonRouteFormRequest $request
     * @param Dungeon                 $dungeon
     * @param DungeonRoute            $dungeonroute
     * @param string|null             $title
     * @return RedirectResponse
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function upgrade(Request $request, Dungeon $dungeon, DungeonRoute $dungeonroute, ?string $title)
    {
        $this->authorize('edit', $dungeonroute);

        // Store it
        $dungeonroute->update([
            'mapping_version_id' => $dungeonroute->dungeon->getCurrentMappingVersion()->id,
            'updated_at'         => Carbon::now()->toDateTimeString(),
        ]);

        // Refresh the enemy forces
        (new RefreshEnemyForces($dungeonroute->id))->handle();

        DungeonRoute::dropCaches($dungeonroute->id);

        // Display the edit page
        return redirect()->route('dungeonroute.edit', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
        ]);
    }
}
