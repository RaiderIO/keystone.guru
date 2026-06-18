<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminToolsDungeonRouteMappingVersionUpgradeRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Mapping\MappingVersion;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class AdminToolsDungeonRouteController extends Controller
{
    public function dungeonrouteView(DungeonRoute $dungeonRoute): View
    {
        $dungeonRoute->load([
            'faction',
            'specializations',
            'classes',
            'races',
            'affixes',
            'brushlines',
            'paths',
            'author',
            'killZones',
            'pridefulEnemies',
            'publishedstate',
            'ratings',
            'favorites',
            'enemyraidmarkers',
            'mapicons',
            'mdtImport',
            'team',
        ]);

        return view('admin.tools.dungeonroute.viewcontents', [
            'dungeonroute' => $dungeonRoute,
        ]);
    }

    public function dungeonroute(): View
    {
        return view('admin.tools.dungeonroute.view');
    }

    public function dungeonroutesubmit(Request $request): View
    {
        $publicKey = $request->get('public_key');

        $dungeonRoute = DungeonRoute::with([
            'faction',
            'specializations',
            'classes',
            'races',
            'affixes',
            'brushlines',
            'paths',
            'author',
            'killZones',
            'pridefulEnemies',
            'publishedstate',
            'ratings',
            'favorites',
            'enemyraidmarkers',
            'mapicons',
            'mdtImport',
            'team',
        ])->when(is_numeric($publicKey), function (Builder $builder) use ($publicKey) {
            $builder->where('id', $publicKey);
        }, function (Builder $builder) use ($publicKey) {
            $builder->where('public_key', $publicKey);
        })->firstOrFail();

        return view('admin.tools.dungeonroute.viewcontents', [
            'dungeonroute' => $dungeonRoute,
        ]);
    }

    public function dungeonrouteMappingVersions(): View
    {
        $allMappingVersions = MappingVersion::with(['dungeon', 'gameVersion'])
            ->withCount('dungeonRoutes')
            ->orderBy('dungeon_id')
            ->orderBy('version')
            ->get();

        // Compute the latest version number per dungeon+gameVersion pair in PHP to avoid N+1 queries
        $latestVersionByGroup = $allMappingVersions
            ->groupBy(static fn(MappingVersion $mv) => sprintf('%d_%d', $mv->dungeon_id, $mv->game_version_id))
            ->map(static fn($group) => $group->max('version'));

        $isLatest = static fn(MappingVersion $mv): bool => $latestVersionByGroup->get(sprintf('%d_%d', $mv->dungeon_id, $mv->game_version_id)) === $mv->version;

        return view('admin.tools.dungeonroute.mappingversions', [
            'unusedMappingVersions' => $allMappingVersions->filter(
                static fn(MappingVersion $mv) => $mv->dungeon_routes_count === 0 && !$isLatest($mv),
            ),
            'usedMappingVersions' => $allMappingVersions->filter(
                static fn(MappingVersion $mv) => $mv->dungeon_routes_count > 0,
            ),
        ]);
    }

    public function dungeonrouteMappingVersionsUpgrade(
        AdminToolsDungeonRouteMappingVersionUpgradeRequest $request,
        DungeonRouteServiceInterface                       $dungeonRouteService,
        MappingVersion                                     $mappingVersion,
    ): RedirectResponse {
        $count = $dungeonRouteService->upgradeMappingVersionBulk($mappingVersion);

        Session::flash('status', __('controller.admintools.flash.mapping_version_upgrade_queued', [
            'count'   => $count,
            'version' => $mappingVersion->getPrettyName(),
        ]));

        return redirect()->route('admin.tools.dungeonroute.mappingversionusage');
    }
}
