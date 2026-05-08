<?php

/** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Http\Controllers;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\CombatLog\ResultEventDungeonRouteServiceInterface;
use App\Service\MapContext\MapContextServiceInterface;
use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use Artisan;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Session;

class AdminToolsController extends Controller
{
    public function index(): View
    {
        return view('admin.tools.list');
    }

    public function combatlog(
        MapContextServiceInterface              $mapContextService,
        ResultEventDungeonRouteServiceInterface $combatLogDungeonRouteService,
    ): View {
        try {
            $dungeonRoutes = $combatLogDungeonRouteService->convertCombatLogToDungeonRoutes(
            //                '/mnt/volume1/media/WoW/combatlogs/DF_S2/WoWCombatLog-050623_221451_19_court-of-stars.zip'
            //                    '/mnt/volume1/media/WoW/combatlogs/DF_S2/WoWCombatLog-050923_172619_10_uldaman-legacy-of-tyr.zip',
                base_path(
                //                    'WoWCombatLog-050923_172619_7_freehold.zip'
                //                    'WoWCombatLog-050923_172619_10_uldaman-legacy-of-tyr.zip'
                //                    'WoWCombatLog-050923_172619_12_neltharions-lair.zip'
                //                    'WoWCombatLog-051023_160438_14_the-underrot.zip'
                //                    'WoWCombatLog-051223_185606_14_brackenhide-hollow.zip'
                //                    'WoWCombatLog-060223_181049_20_brackenhide-hollow.zip',
                //                    'WoWCombatLog-051023_175258_17_the-vortex-pinnacle.zip',
                //                    'WoWCombatLog-060223_181049_20_halls-of-infusion.zip',
                //                'WoWCombatLog-051323_095734_13_neltharus.zip',
                //                'WoWCombatLog-060223_181049_20_neltharus.zip'
                    'tests/CombatLogs/WoWCombatLog-050923_172619_7_freehold.zip',
                //                    'tests/CombatLogs/WoWCombatLog-050923_172619_7_freehold_events.txt'
                //                'tests/Unit/App/Service/CombatLog/Fixtures/2_underrot/WoWCombatLog-051523_211651_2_the-underrot.txt'
                //                'tests/Unit/App/Service/CombatLog/Fixtures/2_underrot/combat.log'
                //                'tests/Unit/App/Service/CombatLog/Fixtures/18_neltharions_lair/combat.log'
                //                    'tests/Unit/App/Service/CombatLog/Fixtures/18_the_vortex_pinnacle/combat.log'
                ),
            );
        } catch (Exception $exception) {
            dd($exception);
        }

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = $dungeonRoutes->first();

        // Reload to re-populate all kinds of fields
        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::find($dungeonRoute->id);

        /** @var Floor $floor */
        $floor = $dungeonRoute->dungeon->floors()->first();

        return view('dungeonroute.edit', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
            'floor'        => $floor,
            'mapContext'   => $mapContextService->createMapContextDungeonRoute($dungeonRoute, User::getCurrentUserMapFacadeStyle()),
            'floorIndex'   => 1,
        ]);
    }

    public function dropCache(Request $request, CacheServiceInterface $cacheService): RedirectResponse
    {
        ini_set('max_execution_time', -1);

        $cacheService->dropCaches();

        Artisan::call('modelCache:clear');

        Artisan::call('keystoneguru:view', ['operation' => 'cache']);

        Session::flash('status', __('controller.admintools.flash.caches_dropped_successfully'));

        return redirect()->route('admin.tools');
    }

    public function mappingForceSync(Request $request): void
    {
        Artisan::call('mapping:sync', ['--force' => true]);
    }

    public function exportreleases(Request $request): View
    {
        Artisan::call('release:save');

        Session::flash('status', __('controller.admintools.flash.releases_exported'));

        return view('admin.tools.list');
    }

    public function toggleReadOnlyMode(
        Request                      $request,
        ReadOnlyModeServiceInterface $readOnlyModeService,
    ): RedirectResponse {
        if ($readOnlyModeService->isReadOnly()) {
            $readOnlyModeService->setReadOnly(false);
            Session::flash('status', __('controller.admintools.flash.read_only_mode_disabled'));
        } else {
            $readOnlyModeService->setReadOnly(true);
            Session::flash('status', __('controller.admintools.flash.read_only_mode_enabled'));
        }

        return redirect()->route('admin.tools');
    }
}
