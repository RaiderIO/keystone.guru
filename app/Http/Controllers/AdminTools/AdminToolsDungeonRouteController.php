<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Mapping\MappingVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminToolsDungeonRouteController extends Controller
{
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
        $mappingVersionUsage = MappingVersion::orderBy('dungeon_id')
            ->get()
            ->mapWithKeys(static fn(
                MappingVersion $mappingVersion,
            ) => [$mappingVersion->getPrettyName() => $mappingVersion->dungeonRoutes()->count()])
            ->groupBy(static fn(int $count, string $key) => $count === 0, true);

        return view('admin.tools.dungeonroute.mappingversions', [
            'mappingVersionUsage' => collect([
                'unused' => $mappingVersionUsage[1],
                'used'   => $mappingVersionUsage[0],
            ]),
        ]);
    }
}
