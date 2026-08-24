<?php

namespace App\Http\Controllers\DungeonRoute;

use App\Http\Controllers\Controller;
use App\Http\Requests\DungeonRoute\DungeonRouteBaseUrlFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteEmbedUrlFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRoutePreviewUrlFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteSubmitFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteSubmitTemporaryFormRequest;
use App\Http\Requests\DungeonRoute\MigrateToSeasonalTypeFormRequest;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\GameServerRegion;
use App\Models\User;
use App\Models\UserReport;
use App\Service\DungeonRoute\DungeonRouteSaveServiceInterface;
use App\Service\DungeonRoute\DungeonRouteUpgradeDraftServiceInterface;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\Floor\FloorResolutionServiceInterface;
use App\Service\MapContext\MapContextServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Psr\SimpleCache\InvalidArgumentException;
use Session;
use Throwable;

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
        DungeonRouteBaseUrlFormRequest  $request,
        FloorResolutionServiceInterface $floorResolutionService,
        Dungeon                         $dungeon,
        DungeonRoute                    $dungeonroute,
        ?string                         $title = null,
    ): RedirectResponse {
        $defaultFloor = $floorResolutionService->resolveDefaultFloor($dungeonroute->dungeon, $dungeonroute->mappingVersion);

        return redirect()->route('dungeonroute.view.floor', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'floorIndex'   => $defaultFloor->index,
        ] + $request->validated());
    }

    /**
     * @return Factory|RedirectResponse|View
     *
     * @throws AuthorizationException
     */
    public function viewFloor(
        DungeonRouteBaseUrlFormRequest  $request,
        MapContextServiceInterface      $mapContextService,
        ThumbnailServiceInterface       $thumbnailService,
        FloorResolutionServiceInterface $floorResolutionService,
        Dungeon                         $dungeon,
        DungeonRoute                    $dungeonroute,
        string                          $title,
        string                          $floorIndex,
    ) {
        Gate::authorize('view', $dungeonroute);

        if ($dungeonroute->getTitleSlug() !== $title) {
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

        $resolvedFloor = $floorResolutionService->resolveRequestedFloor($dungeonroute->dungeon, $dungeonroute->mappingVersion, $floorIndex);

        if (!$resolvedFloor->isCanonical) {
            return redirect()->route('dungeonroute.view.floor', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
                'floorIndex'   => $resolvedFloor->floor->index,
            ] + $request->validated());
        } else {
            $floor = $resolvedFloor->floor;

            // If we viewed a route, then there should also be a thumbnail for it
            $thumbnailService->queueThumbnailRefreshIfMissing(collect([$dungeonroute]));

            return view('dungeonroute.view', [
                'dungeon'        => $dungeonroute->dungeon,
                'dungeonroute'   => $dungeonroute,
                'title'          => $dungeonroute->getTitleSlug(),
                'current_report' => $currentReport,
                'floor'          => $floor,
                'parameters'     => $request->validated(),
                'mapContext'     => $mapContextService->createMapContextDungeonRoute($dungeonroute, User::getCurrentUserMapFacadeStyle()),
            ]);
        }
    }

    /**
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function present(
        DungeonRouteBaseUrlFormRequest  $request,
        FloorResolutionServiceInterface $floorResolutionService,
        Dungeon                         $dungeon,
        DungeonRoute                    $dungeonroute,
        ?string                         $title = null,
    ): RedirectResponse {
        $defaultFloor = $floorResolutionService->resolveDefaultFloor($dungeonroute->dungeon, $dungeonroute->mappingVersion);

        return redirect()->route('dungeonroute.present.floor', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'floorIndex'   => $defaultFloor->index,
        ] + $request->validated());
    }

    /**
     * @return Factory|RedirectResponse|View
     *
     * @throws AuthorizationException
     */
    public function presentFloor(
        DungeonRouteBaseUrlFormRequest  $request,
        MapContextServiceInterface      $mapContextService,
        FloorResolutionServiceInterface $floorResolutionService,
        Dungeon                         $dungeon,
        DungeonRoute                    $dungeonroute,
        string                          $title,
        string                          $floorIndex,
    ) {
        Gate::authorize('present', $dungeonroute);

        // @TODO fix this - it has a different connection and that messes with the relation
        $challengeModeRun = ChallengeModeRun::firstWhere('dungeon_route_id', $dungeonroute->id);

        if ($challengeModeRun === null) {
            abort(403, 'Route not generated from API!');
        }

        $dungeonroute->setRelation('challengeModeRun', $challengeModeRun);

        if ($dungeonroute->getTitleSlug() !== $title) {
            return redirect()->route('dungeonroute.present', [
                'dungeon'      => $dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
            ] + $request->validated());
        }

        $dungeonroute->trackPageView(DungeonRoute::PAGE_VIEW_SOURCE_PRESENT_ROUTE);

        $resolvedFloor = $floorResolutionService->resolveRequestedFloor($dungeonroute->dungeon, $dungeonroute->mappingVersion, $floorIndex);

        if (!$resolvedFloor->isCanonical) {
            return redirect()->route('dungeonroute.present.floor', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
                'floorIndex'   => $resolvedFloor->floor->index,
            ] + $request->validated());
        } else {
            return view('dungeonroute.present', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
                'floor'        => $resolvedFloor->floor,
                'parameters'   => $request->validated(),
                'mapContext'   => $mapContextService->createMapContextDungeonRoute($dungeonroute, User::getCurrentUserMapFacadeStyle()),
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
        string                            $floorIndex,
    ) {
        Gate::authorize('preview', [
            $dungeonroute,
            $request->get('secret', '') ?? '',
        ]);

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        $zoomLevel = $request->get('z');

        $titleSlug = $dungeonroute->getTitleSlug();
        if ($titleSlug !== $title) {
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

        // Override config value so puppeteer can access the assets
        if (Auth::user() === null) {
            Config::set('keystoneguru.assets_base_url', config('keystoneguru.assets_base_url_internal'));
            Config::set('keystoneguru.tiles_base_url', config('keystoneguru.tiles_base_url_internal'));
        }

        return view('dungeonroute.preview', [
            'dungeonroute'   => $dungeonroute,
            'floor'          => $floor,
            'mapContext'     => $mapContextService->createMapContextDungeonRoute($dungeonroute, $mapFacadeStyle),
            'defaultZoom'    => $zoomLevel,
            'mapFacadeStyle' => $mapFacadeStyle,
            // The factor to multiply the killzone-path (pull-connection) line weight by so a small miniature
            // still reads as a route shape. Null (the default) keeps the map's normal line width.
            'killZonePathWeightMultiplier' => $request->has('killzonepathweight') ? (float)$request->input('killzonepathweight') : null,
            'parameters'                   => $request->validated(),
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
        string                           $seasonalType,
    ): RedirectResponse {
        Gate::authorize('migrate', $dungeonroute);

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
        DungeonRouteSubmitFormRequest    $request,
        DungeonRouteSaveServiceInterface $saveService,
        ?DungeonRoute                    $dungeonroute = null,
    ): DungeonRoute {
        if ($dungeonroute === null) {
            $dungeonroute = new DungeonRoute();
        }

        // May fail
        if (!$saveService->save($dungeonroute, $request->validated())) {
            abort(500, __('controller.dungeonroute.unable_to_save'));
        }

        return $dungeonroute;
    }

    /**
     * @throws Exception
     */
    public function storeTemporary(
        DungeonRouteSubmitTemporaryFormRequest $request,
        DungeonRouteSaveServiceInterface       $saveService,
    ): DungeonRoute {
        $dungeonroute = new DungeonRoute();

        // May fail
        if (!$saveService->saveTemporary($dungeonroute, $request->validated())) {
            abort(500, __('controller.dungeonroute.unable_to_save'));
        }

        return $dungeonroute;
    }

    /**
     * @return Application|RedirectResponse|Redirector|View
     *
     * @throws AuthorizationException
     */
    public function copy(
        Request                          $request,
        Dungeon                          $dungeon,
        DungeonRoute                     $dungeonroute,
        string                           $title,
        DungeonRouteSaveServiceInterface $saveService,
    ) {
        Gate::authorize('clone', $dungeonroute);

        /** @var User $user */
        $user = Auth::user();

        if ($user->canCreateDungeonRoute()) {
            $newRoute = $saveService->cloneRoute($dungeonroute);

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
     * @throws AuthorizationException
     */
    public function claim(
        Request      $request,
        Dungeon      $dungeon,
        DungeonRoute $dungeonroute,
        string       $title,
    ): RedirectResponse {
        Gate::authorize('claim', $dungeonroute);

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
        DungeonRouteBaseUrlFormRequest  $request,
        FloorResolutionServiceInterface $floorResolutionService,
        Dungeon                         $dungeon,
        DungeonRoute                    $dungeonroute,
        ?string                         $title = null,
    ): RedirectResponse {
        $defaultFloor = $floorResolutionService->resolveDefaultFloor($dungeonroute->dungeon, $dungeonroute->mappingVersion);

        return redirect()->route('dungeonroute.edit.floor', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->getTitleSlug(),
            'floorIndex'   => $defaultFloor->index,
        ] + $request->validated());
    }

    /**
     * @return Factory|RedirectResponse|View
     *
     * @throws AuthorizationException
     */
    public function editFloor(
        MapContextServiceInterface      $mapContextService,
        SeasonServiceInterface          $seasonService,
        DungeonRouteBaseUrlFormRequest  $request,
        FloorResolutionServiceInterface $floorResolutionService,
        Dungeon                         $dungeon,
        DungeonRoute                    $dungeonroute,
        ?string                         $title,
        ?string                         $floorIndex,
    ) {
        Gate::authorize('edit', $dungeonroute);

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

        $resolvedFloor = $floorResolutionService->resolveRequestedFloor($dungeonroute->dungeon, $dungeonroute->mappingVersion, $floorIndex);

        if (!$resolvedFloor->isCanonical) {
            return redirect()->route('dungeonroute.edit.floor', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
                'floorIndex'   => $resolvedFloor->floor->index,
            ] + $request->validated());
        } else {
            $floor = $resolvedFloor->floor;

            $userOrDefaultRegion = GameServerRegion::getUserOrDefaultRegion();

            $season = $seasonService->getSeasonAt(
                $dungeonroute->created_at,
                $dungeonroute->dungeon->expansion,
                $userOrDefaultRegion,
            ) ?? $seasonService->getCurrentSeason($dungeonroute->dungeon->expansion, $userOrDefaultRegion);

            return view('dungeonroute.edit', [
                'dungeon'      => $dungeonroute->dungeon,
                'dungeonroute' => $dungeonroute,
                'title'        => $dungeonroute->getTitleSlug(),
                'floor'        => $floor,
                'mapContext'   => $mapContextService->createMapContextDungeonRoute($dungeonroute, User::getCurrentUserMapFacadeStyle()),
                'floorIndex'   => $floorIndex,
                'keyLevelMin'  => $season?->key_level_min ?? config('keystoneguru.keystone.levels.default_min'), // @phpstan-ignore nullsafe.neverNull
                'keyLevelMax'  => $season?->key_level_max ?? config('keystoneguru.keystone.levels.default_max'), // @phpstan-ignore nullsafe.neverNull
                'parameters'   => $request->validated(),
            ]);
        }
    }

    /**
     * @return Application|Factory|View|Response
     *
     * @throws AuthorizationException
     */
    public function embed(
        DungeonRouteEmbedUrlFormRequest $request,
        MapContextServiceInterface      $mapContextService,
        FloorResolutionServiceInterface $floorResolutionService,
        Dungeon                         $dungeon,
        DungeonRoute                    $dungeonroute,
        ?string                         $title = null,
        string                          $floorIndex = '1',
    ) {
        Gate::authorize('embed', $dungeonroute);

        $dungeonroute->trackPageView(DungeonRoute::PAGE_VIEW_SOURCE_VIEW_EMBED);

        $locale = $request->get('locale', App::getLocale());
        App::setLocale(
            config('language.short_to_long')[$locale] ?? $locale,
        );

        // Ensure that User::getCurrentUserMapFacadeStyle() returns the wanted map facade style
        $mapFacadeStyle = $request->get('mapFacadeStyle', User::getCurrentUserMapFacadeStyle());
        User::forceMapFacadeStyle($mapFacadeStyle);

        $floor = $floorResolutionService->resolveRequestedFloor($dungeonroute->dungeon, $dungeonroute->mappingVersion, $floorIndex)->floor;

        $validated = $request->validated();

        $style                 = $validated['style'] ?? 'regular';
        $pullsDefaultState     = $validated['pullsDefaultState'] ?? null;
        $pullsHideOnMove       = $validated['pullsHideOnMove'] ?? null;
        $headerBackgroundColor = $validated['headerBackgroundColor'] ?? null;
        $mapBackgroundColor    = $validated['mapBackgroundColor'] ?? null;

        $showEnemyInfo       = $validated['showEnemyInfo'] ?? false;
        $showPulls           = $validated['showPulls'] ?? true;
        $showEnemyForces     = $validated['showEnemyForces'] ?? true;
        $showAffixes         = $validated['showAffixes'] ?? true;
        $showTitle           = $validated['showTitle'] ?? true;
        $showPresenterButton = $validated['showPresenterButton'] ?? false;
        $showHeader          = $validated['showHeader'] ?? true;

        return view('dungeonroute.embed', [
            'dungeon' => $dungeonroute->dungeon->load([
                'expansion',
                'floors',
            ]),
            'dungeonroute'   => $dungeonroute,
            'title'          => $dungeonroute->getTitleSlug(),
            'floor'          => $floor,
            'mapFacadeStyle' => $mapFacadeStyle,
            'mapContext'     => $mapContextService->createMapContextDungeonRoute($dungeonroute, User::getCurrentUserMapFacadeStyle()),
            'parameters'     => $validated,
            'embedOptions'   => [
                'style' => $style,
                // Null if not set - but cast to a bool if it is ("0" or 0 both equal false, "1" or 1 both equal true
                'pullsDefaultState' => (int)$pullsDefaultState,
                // Default false - closed
                'pullsHideOnMove'       => $pullsHideOnMove === null ? null : (bool)$pullsHideOnMove,
                'headerBackgroundColor' => $headerBackgroundColor,
                'mapBackgroundColor'    => $mapBackgroundColor,
                'show'                  => [
                    // Default false - not available
                    'enemyInfo' => (bool)$showEnemyInfo,
                    // Default true - available
                    'pulls' => (bool)$showPulls,
                    // Default true - available
                    'enemyForces' => (bool)$showEnemyForces,
                    // Default true - available
                    'affixes' => (bool)$showAffixes,
                    // Default true - available
                    'title' => (bool)$showTitle,
                    // Default false, not available
                    'presenterButton' => (bool)$showPresenterButton,
                    // Always available, but can be overridden later if there's no floors to select
                    'floorSelection' => true,
                    // Default false, not documented, hides the entire embed header when false
                    'header' => (bool)$showHeader,
                ],
            ],
        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function update(
        DungeonRouteSubmitFormRequest    $request,
        DungeonRouteSaveServiceInterface $saveService,
        DungeonRoute                     $dungeonroute,
    ): RedirectResponse {
        Gate::authorize('edit', $dungeonroute);

        // Store it and show the edit page again
        $dungeonroute = $this->store($request, $saveService);

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
        DungeonRouteSubmitFormRequest    $request,
        DungeonRouteSaveServiceInterface $saveService,
    ): RedirectResponse {
        // Store it and show the edit page
        $dungeonRoute = $this->store($request, $saveService);

        // Message to the user
        Session::flash('status', __('controller.dungeonroute.flash.route_created'));

        return redirect()->route('dungeonroute.edit', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function saveNewTemporary(
        DungeonRouteSubmitTemporaryFormRequest $request,
        DungeonRouteSaveServiceInterface       $saveService,
    ): RedirectResponse {
        // Store it and show the edit page
        $dungeonroute = $this->storeTemporary($request, $saveService);

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
     */
    public function upgrade(
        DungeonRouteUpgradeDraftServiceInterface $dungeonRouteUpgradeDraftService,
        Dungeon                                  $dungeon,
        DungeonRoute                             $dungeonroute,
        ?string                                  $title,
    ): RedirectResponse {
        Gate::authorize('edit', $dungeonroute);

        // Upgrading no longer mutates the live route - the author repairs a draft while the original
        // keeps serving its old, intact content
        $draft = $dungeonRouteUpgradeDraftService->findOrCreateDraft($dungeonroute);

        return redirect()->route('dungeonroute.edit', [
            'dungeon'      => $draft->dungeon,
            'dungeonroute' => $draft,
            'title'        => $draft->getTitleSlug(),
        ])->with('status', __('controller.dungeonroute.flash.upgrade_draft_created'));
    }

    /**
     * Applies an upgrade draft onto the route it is a draft of, replacing that route's contents and
     * settings while preserving its identity.
     *
     * @param DungeonRoute $dungeonroute The DRAFT, not the route being replaced.
     *
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function applyUpgrade(
        Request                                  $request,
        DungeonRouteUpgradeDraftServiceInterface $dungeonRouteUpgradeDraftService,
        Dungeon                                  $dungeon,
        DungeonRoute                             $dungeonroute,
        ?string                                  $title,
    ): RedirectResponse|JsonResponse {
        Gate::authorize('applyUpgrade', $dungeonroute);

        $original = $dungeonRouteUpgradeDraftService->apply($dungeonroute);

        return $this->redirectAfterUpgradeDraftAction(
            $request,
            route('dungeonroute.edit', [
                'dungeon'      => $original->dungeon,
                'dungeonroute' => $original,
                'title'        => $original->getTitleSlug(),
            ]),
            __('controller.dungeonroute.flash.upgrade_applied'),
        );
    }

    /**
     * Discards an upgrade draft, leaving the route it is a draft of untouched.
     *
     * @param DungeonRoute $dungeonroute The DRAFT, not the route it upgrades.
     *
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function discardUpgrade(
        Request                                  $request,
        DungeonRouteUpgradeDraftServiceInterface $dungeonRouteUpgradeDraftService,
        Dungeon                                  $dungeon,
        DungeonRoute                             $dungeonroute,
        ?string                                  $title,
    ): RedirectResponse|JsonResponse {
        Gate::authorize('discardUpgrade', $dungeonroute);

        $original = $dungeonroute->upgradeOfDungeonRoute;

        $dungeonRouteUpgradeDraftService->discard($dungeonroute);

        // The original can be gone if it was deleted while the draft was open - deleting an original
        // deletes its draft, but the model handed to this request was resolved before that
        $redirectUrl = $original === null
            ? route('profile.routes')
            : route('dungeonroute.edit', [
                'dungeon'      => $original->dungeon,
                'dungeonroute' => $original,
                'title'        => $original->getTitleSlug(),
            ]);

        return $this->redirectAfterUpgradeDraftAction(
            $request,
            $redirectUrl,
            __('controller.dungeonroute.flash.upgrade_discarded'),
        );
    }

    /**
     * The apply/discard buttons post over ajax, so hand those requests the redirect target as JSON
     * rather than a 302 the caller cannot follow.
     */
    private function redirectAfterUpgradeDraftAction(Request $request, string $redirectUrl, string $status): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'redirect_url' => $redirectUrl,
                'status'       => $status,
            ]);
        }

        return redirect()->to($redirectUrl)->with('status', $status);
    }
}
