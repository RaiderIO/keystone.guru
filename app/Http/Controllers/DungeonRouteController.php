<?php

namespace App\Http\Controllers;

use App\Http\Requests\DungeonRoute\DungeonRouteBaseUrlFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteEmbedUrlFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRoutePreviewUrlFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteSubmitFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteSubmitTemporaryFormRequest;
use App\Http\Requests\DungeonRoute\MigrateToSeasonalTypeFormRequest;
use App\Jobs\RefreshEnemyForces;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\GameServerRegion;
use App\Models\User;
use App\Models\UserReport;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\MapContext\MapContextServiceInterface;
use App\Service\Season\SeasonServiceInterface;
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
use Psr\SimpleCache\InvalidArgumentException;
use Session;
use Teapot\StatusCode\Http;

class DungeonRouteController extends Controller
{
    public function create(): View
    {
        return view('dungeonroute.new');
    }

    public function createTemporary(): View
    {
        return view('dungeonroute.newtemporary', ['dungeons' => Dungeon::all()]);
    }

    /**
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function view(
        DungeonRouteBaseUrlFormRequest $request,
        Dungeon                        $dungeon,
        DungeonRoute                   $dungeonroute,
        ?string                        $title = null
    ): RedirectResponse {
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)
            ->defaultOrFacade($dungeonroute->mappingVersion)
            ->first();

        return redirect()->route('dungeonroute.view.floor', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
                'floorIndex'   => $defaultFloor?->index ?? '1',
            ] + $request->validated());
    }

    /**
     * @return Factory|RedirectResponse|View
     *
     * @throws AuthorizationException
     */
    public function viewFloor(
        DungeonRouteBaseUrlFormRequest $request,
        MapContextServiceInterface     $mapContextService,
        ThumbnailServiceInterface      $thumbnailService,
        Dungeon                        $dungeon,
        DungeonRoute                   $dungeonroute,
        string                         $title,
        string                         $floorIndex
    ) {
        $this->authorize('view', $dungeonroute);

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        if (!isset($title) || $dungeonroute->getTitleSlug() !== $title) {
            return redirect()->route('dungeonroute.view', [
                    'dungeon'      => $dungeon,
                    'dungeonroute' => $dungeonroute,
                    'title'        => $dungeonroute->getTitleSlug(),
                ] + $request->validated());
        }

        $currentReport = null;
        if (Auth::check()) {
            // Find any currently active report the user has made
            $currentReport = UserReport::where('user_id', Auth::id())
                ->where('model_id', $dungeonroute->id)
                ->where('model_class', $dungeonroute::class)
                ->where('category', 'dungeonroute')
                ->where('status', 0)
                ->first();
        }

        $dungeonroute->trackPageView(DungeonRoute::PAGE_VIEW_SOURCE_VIEW_ROUTE);

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)
            ->indexOrFacade($dungeonroute->mappingVersion, $floorIndex)
            ->first();

        if ($floor === null) {
            /** @var Floor $defaultFloor */
            $defaultFloor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)
                ->defaultOrFacade($dungeonroute->mappingVersion)
                ->first();

            return redirect()->route('dungeonroute.view.floor', [
                    'dungeon'      => $dungeonroute->dungeon,
                    'dungeonroute' => $dungeonroute,
                    'title'        => $dungeonroute->getTitleSlug(),
                    'floorIndex'   => $defaultFloor?->index ?? '1',
                ] + $request->validated());
        } else {
            if ($floor->index !== (int)$floorIndex) {
                return redirect()->route('dungeonroute.view.floor', [
                        'dungeon'      => $dungeonroute->dungeon,
                        'dungeonroute' => $dungeonroute,
                        'title'        => $dungeonroute->getTitleSlug(),
                        'floorIndex'   => $floor->index,
                    ] + $request->validated());
            }

            // If we viewed a route, then there should also be a thumbnail for it
            $thumbnailService->queueThumbnailRefreshIfMissing(collect([$dungeonroute]));

            return view('dungeonroute.view', [
                'dungeon'        => $dungeonroute->dungeon,
                'dungeonroute'   => $dungeonroute,
                'title'          => $dungeonroute->getTitleSlug(),
                'current_report' => $currentReport,
                'floor'          => $floor,
                'parameters'     => $request->validated(),
                'mapContext'     => $mapContextService->createMapContextDungeonRoute($dungeonroute, $floor),
            ]);
        }
    }

    /**
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function present(
        DungeonRouteBaseUrlFormRequest $request,
        Dungeon                        $dungeon,
        DungeonRoute                   $dungeonroute,
        ?string                        $title = null
    ): RedirectResponse {
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)
            ->defaultOrFacade($dungeonroute->mappingVersion)
            ->first();

        return redirect()->route('dungeonroute.present.floor', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
                'floorIndex'   => $defaultFloor?->index ?? '1',
            ] + $request->validated());
    }

    /**
     * @return Factory|RedirectResponse|View
     *
     * @throws AuthorizationException
     */
    public function presentFloor(
        DungeonRouteBaseUrlFormRequest $request,
        MapContextServiceInterface     $mapContextService,
        Dungeon                        $dungeon,
        DungeonRoute                   $dungeonroute,
        string                         $title,
        string                         $floorIndex)
    {
        $this->authorize('present', $dungeonroute);

        // @TODO fix this - it has a different connection and that messes with the relation
        $challengeModeRun = ChallengeModeRun::firstWhere('dungeon_route_id', $dungeonroute->id);

        if ($challengeModeRun === null) {
            abort(403, 'Route not generated from API!');
        }

        $dungeonroute->setRelation('challengeModeRun', $challengeModeRun);

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        if (!isset($title) || $dungeonroute->getTitleSlug() !== $title) {
            return redirect()->route('dungeonroute.present', [
                    'dungeon'      => $dungeon,
                    'dungeonroute' => $dungeonroute,
                    'title'        => $dungeonroute->getTitleSlug(),
                ] + $request->validated());
        }

        $dungeonroute->trackPageView(DungeonRoute::PAGE_VIEW_SOURCE_PRESENT_ROUTE);

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)
            ->indexOrFacade($dungeonroute->mappingVersion, $floorIndex)
            ->first();

        if ($floor === null) {
            /** @var Floor $defaultFloor */
            $defaultFloor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)
                ->defaultOrFacade($dungeonroute->mappingVersion)
                ->first();

            return redirect()->route('dungeonroute.present.floor', [
                    'dungeon'      => $dungeonroute->dungeon,
                    'dungeonroute' => $dungeonroute,
                    'title'        => $dungeonroute->getTitleSlug(),
                    'floorIndex'   => $defaultFloor?->index ?? '1',
                ] + $request->validated());
        } else {
            if ($floor->index !== (int)$floorIndex) {
                return redirect()->route('dungeonroute.present.floor', [
                        'dungeon'      => $dungeonroute->dungeon,
                        'dungeonroute' => $dungeonroute,
                        'title'        => $dungeonroute->getTitleSlug(),
                        'floorIndex'   => $floor->index,
                    ] + $request->validated());
            }

            return view('dungeonroute.present', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
                'floor'        => $floor,
                'parameters'   => $request->validated(),
                'mapContext'   => $mapContextService->createMapContextDungeonRoute($dungeonroute, $floor),
            ]);
        }
    }

    /**
     * @return Factory|RedirectResponse|View
     *
     * @throws AuthorizationException
     */
    public function preview(
        DungeonRoutePreviewUrlFormRequest $request,
        MapContextServiceInterface        $mapContextService,
        Dungeon                           $dungeon,
        DungeonRoute                      $dungeonroute,
        string                            $title,
        string                            $floorIndex
    ) {
        $this->authorize('preview', [$dungeonroute, $request->get('secret', '') ?? '']);

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        $zoomLevel = $request->get('z');

        $titleSlug = $dungeonroute->getTitleSlug();
        if (!isset($title) || $titleSlug !== $title) {
            return redirect()->route('dungeonroute.preview', [
                    'dungeon'      => $dungeon,
                    'dungeonroute' => $dungeonroute,
                    'title'        => $titleSlug,
                    'floorIndex'   => $floorIndex,
                ] + $request->validated());
        }

        /** @var FLoor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)
            // Force usage of facade if requested
            ->where('index', $floorIndex)
            ->first();

        $mapFacadeStyle = $floor->facade ? User::MAP_FACADE_STYLE_FACADE : User::MAP_FACADE_STYLE_SPLIT_FLOORS;

        return view('dungeonroute.preview', [
            'dungeonroute'   => $dungeonroute,
            'floorId'        => $floor->id,
            'mapContext'     => $mapContextService->createMapContextDungeonRoute($dungeonroute, $floor, $mapFacadeStyle),
            'defaultZoom'    => $zoomLevel,
            'mapFacadeStyle' => $mapFacadeStyle,
            'parameters'     => $request->validated(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function migrateToSeasonalType(
        ExpansionServiceInterface        $expansionService,
        MigrateToSeasonalTypeFormRequest $request,
        Dungeon                          $dungeon,
        DungeonRoute                     $dungeonroute,
        string                           $title,
        string                           $seasonalType
    ): RedirectResponse {
        $this->authorize('migrate', $dungeonroute);

        $dungeonroute->migrateToSeasonalType($expansionService, $seasonalType);

        return redirect()->route('dungeonroute.edit', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $title,
            ] + $request->validated());
    }

    /**
     * @throws Exception
     */
    public function store(
        DungeonRouteSubmitFormRequest $request,
        SeasonServiceInterface        $seasonService,
        ExpansionServiceInterface     $expansionService,
        ThumbnailServiceInterface     $thumbnailService,
        ?DungeonRoute                 $dungeonroute = null
    ): DungeonRoute {


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
     * @throws Exception
     */
    public function storeTemporary(
        DungeonRouteSubmitTemporaryFormRequest $request,
        SeasonServiceInterface                 $seasonService,
        ExpansionServiceInterface              $expansionService
    ): DungeonRoute {
        $dungeonroute = new DungeonRoute();

        // May fail
        if (!$dungeonroute->saveTemporaryFromRequest($request, $seasonService, $expansionService)) {
            abort(500, __('controller.dungeonroute.unable_to_save'));
        }

        return $dungeonroute;
    }

    /**
     * @return Application|RedirectResponse|Redirector
     *
     * @throws AuthorizationException
     */
    public function copy(Request $request, Dungeon $dungeon, DungeonRoute $dungeonroute, string $title, ThumbnailServiceInterface $thumbnailService)
    {
        $this->authorize('clone', $dungeonroute);

        /** @var User $user */
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

    public function claim(Request $request, Dungeon $dungeon, DungeonRoute $dungeonroute, string $title): RedirectResponse
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
     * @throws InvalidArgumentException
     */
    public function edit(
        DungeonRouteBaseUrlFormRequest $request,
        Dungeon                        $dungeon,
        DungeonRoute                   $dungeonroute,
        ?string                        $title = null
    ): RedirectResponse {
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)
            ->defaultOrFacade($dungeonroute->mappingVersion)
            ->first();

        return redirect()->route('dungeonroute.edit.floor', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
                'floorIndex'   => $defaultFloor?->index ?? '1',
            ] + $request->validated());
    }

    /**
     * @return Factory|RedirectResponse|View
     *
     * @throws AuthorizationException
     */
    public function editFloor(
        MapContextServiceInterface     $mapContextService,
        SeasonServiceInterface         $seasonService,
        DungeonRouteBaseUrlFormRequest $request,
        Dungeon                        $dungeon,
        DungeonRoute                   $dungeonroute,
        ?string                        $title,
        ?string                        $floorIndex)
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
                    'floorIndex'   => $floorIndex,
                ] + $request->validated());
        }

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)
            ->indexOrFacade($dungeonroute->mappingVersion, $floorIndex)
            ->first();

        if ($floor === null) {
            /** @var Floor $defaultFloor */
            $defaultFloor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)
                ->defaultOrFacade($dungeonroute->mappingVersion)
                ->first();

            return redirect()->route('dungeonroute.edit.floor', [
                    'dungeon'      => $dungeonroute->dungeon,
                    'dungeonroute' => $dungeonroute,
                    'title'        => $dungeonroute->getTitleSlug(),
                    'floorIndex'   => $defaultFloor?->index ?? '1',
                ] + $request->validated());
        } else {
            if ($floor->index !== (int)$floorIndex) {
                return redirect()->route('dungeonroute.edit.floor', [
                        'dungeon'      => $dungeonroute->dungeon,
                        'dungeonroute' => $dungeonroute,
                        'title'        => $dungeonroute->getTitleSlug(),
                        'floorIndex'   => $floor->index,
                    ] + $request->validated());
            }

            $userOrDefaultRegion = GameServerRegion::getUserOrDefaultRegion();

            $season = $seasonService->getSeasonAt(
                $dungeonroute->created_at,
                $dungeonroute->dungeon->expansion,
                $userOrDefaultRegion
            ) ?? $seasonService->getCurrentSeason($dungeonroute->dungeon->expansion, $userOrDefaultRegion);

            return view('dungeonroute.edit', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
                'floor'        => $floor,
                'mapContext'   => $mapContextService->createMapContextDungeonRoute($dungeonroute, $floor),
                'floorIndex'   => $floorIndex,
                'keyLevelMin'  => $season?->key_level_min ?? config('keystoneguru.keystone.levels.default_min'),
                'keyLevelMax'  => $season?->key_level_max ?? config('keystoneguru.keystone.levels.default_max'),
                'parameters'   => $request->validated(),
            ]);
        }
    }

    /**
     * @param mixed $dungeonroute
     * @return Application|Factory|View
     *
     * @throws AuthorizationException
     */
    public function embed(
        DungeonRouteEmbedUrlFormRequest $request,
        MapContextServiceInterface      $mapContextService,
        mixed                           $dungeonroute,
        string                          $floorIndex = '1')
    {
        if (!is_numeric($floorIndex)) {
            $dungeonroute = DungeonRoute::where('public_key', $floorIndex)->first();
            if ($dungeonroute === null) {
                return response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
            }
        }

        $this->authorize('embed', $dungeonroute);

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        $dungeonroute->trackPageView(DungeonRoute::PAGE_VIEW_SOURCE_VIEW_EMBED);

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeonroute->dungeon_id)
            ->indexOrFacade($dungeonroute->mappingVersion, $floorIndex)
            ->first();

        $style                 = $request->get('style', 'regular');
        $pullsDefaultState     = $request->get('pullsDefaultState');
        $pullsHideOnMove       = $request->get('pullsHideOnMove');
        $headerBackgroundColor = $request->get('headerBackgroundColor');
        $mapBackgroundColor    = $request->get('mapBackgroundColor');

        $showEnemyInfo       = $request->get('showEnemyInfo', false);
        $showPulls           = $request->get('showPulls', true);
        $showEnemyForces     = $request->get('showEnemyForces', true);
        $showAffixes         = $request->get('showAffixes', true);
        $showTitle           = $request->get('showTitle', true);
        $showPresenterButton = $request->get('showPresenterButton', false);

        return view('dungeonroute.embed', [
            'dungeon'      => $dungeonroute->dungeon->load(['expansion', 'floors']),
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'floor'        => $floor,
            'mapContext'   => $mapContextService->createMapContextDungeonRoute($dungeonroute, $floor),
            'parameters'   => $request->validated(),
            'embedOptions' => [
                'style'                 => $style,
                // Null if not set - but cast to a bool if it is ("0" or 0 both equal false, "1" or 1 both equal true
                'pullsDefaultState'     => (int)$pullsDefaultState, // Default false - closed
                'pullsHideOnMove'       => $pullsHideOnMove === null ? null : (bool)$pullsHideOnMove,
                'headerBackgroundColor' => $headerBackgroundColor,
                'mapBackgroundColor'    => $mapBackgroundColor,
                'show'                  => [
                    'enemyInfo'       => (bool)$showEnemyInfo,       // Default false - not available
                    'pulls'           => (bool)$showPulls,           // Default true - available
                    'enemyForces'     => (bool)$showEnemyForces,     // Default true - available
                    'affixes'         => (bool)$showAffixes,         // Default true - available
                    'title'           => (bool)$showTitle,           // Default true - available
                    'presenterButton' => (bool)$showPresenterButton, // Default false, not available
                    'floorSelection'  => true,                       // Always available, but can be overridden later if there's no floors to select
                ],
            ],
        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function update(
        DungeonRouteSubmitFormRequest $request,
        SeasonServiceInterface        $seasonService,
        ExpansionServiceInterface     $expansionService,
        ThumbnailServiceInterface     $thumbnailService,
        DungeonRoute                  $dungeonroute
    ): RedirectResponse {
        $this->authorize('edit', $dungeonroute);

        // Store it and show the edit page again
        $dungeonroute = $this->store($request, $seasonService, $expansionService, $thumbnailService);

        // Message to the user
        Session::flash('status', __('controller.dungeonroute.flash.route_updated'));

        // Display the edit page
        return redirect()->route('dungeonroute.edit', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function saveNew(
        DungeonRouteSubmitFormRequest $request,
        SeasonServiceInterface        $seasonService,
        ExpansionServiceInterface     $expansionService,
        ThumbnailServiceInterface     $thumbnailService
    ): RedirectResponse {
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
     * @throws Exception
     */
    public function saveNewTemporary(
        DungeonRouteSubmitTemporaryFormRequest $request,
        SeasonServiceInterface                 $seasonService,
        ExpansionServiceInterface              $expansionService
    ): RedirectResponse {
        // Store it and show the edit page
        $dungeonroute = $this->storeTemporary($request, $seasonService, $expansionService);

        // Message to the user
        Session::flash('status', __('controller.dungeonroute.flash.route_created'));

        return redirect()->route('dungeonroute.edit', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function upgrade(Request $request, Dungeon $dungeon, DungeonRoute $dungeonroute, ?string $title): RedirectResponse
    {
        $this->authorize('edit', $dungeonroute);

        // Store it
        $dungeonroute->update([
            'mapping_version_id' => $dungeonroute->dungeon->currentMappingVersion->id,
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
