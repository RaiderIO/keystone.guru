<?php

namespace App\Http\Controllers\DungeonRoute;

use App\Http\Controllers\Controller;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\User;
use App\Service\MapContext\MapContextServiceInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\View\View;

class DungeonRouteSearchController extends Controller
{
    /**
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function search(
        Request $request,
    ): RedirectResponse {
        return redirect()->route('dungeon.dungeonroute.search.gameversion', [
            'gameVersion' => GameVersion::getUserOrDefaultGameVersion(),
        ]);
    }

    /**
     * @return Factory|View
     */
    public function searchByGameVersion(
        Request     $request,
        GameVersion $gameVersion,
    ): View {
        return view('dungeon.dungeonroute.search.list', [
            'gameVersion' => $gameVersion,
        ]);
    }

    public function searchByDungeon(
        FormRequest                $request,
        GameVersion                $gameVersion,
        Dungeon                    $dungeon,
        MapContextServiceInterface $mapContextService,
    ): View {
        $mappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)
            ->defaultOrFacade($mappingVersion)
            ->first();

        return view('dungeon.dungeonroute.search.gameversion.dungeon', [
            'dungeon'     => $dungeon,
            'floor'       => $floor,
            'parameters'  => $request->validated(),
            'title'       => __($dungeon->name),
            'mapContext'  => $mapContextService->createMapContextDungeonRouteSearch($dungeon, $mappingVersion, User::getCurrentUserMapFacadeStyle()),
            'keyLevelMin' => $season?->key_level_min ?? config('keystoneguru.keystone.levels.default_min'),
            'keyLevelMax' => $season?->key_level_max ?? config('keystoneguru.keystone.levels.default_max'),
        ]);
    }
}
