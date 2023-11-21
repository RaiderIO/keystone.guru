<?php

namespace App\Http\Controllers\Dungeon;

use App\Http\Controllers\Controller;
use App\Logic\MapContext\MapContextDungeonExplore;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Service\MapContext\MapContextServiceInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Psr\SimpleCache\InvalidArgumentException;

class DungeonExploreController extends Controller
{
    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function list(Request $request)
    {
        return view('dungeon.explore.list');
    }

    /**
     * @param Request $request
     * @param Dungeon $dungeon
     *
     * @return mixed
     */
    public function viewDungeon(Request $request, Dungeon $dungeon)
    {
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)->where('default', true)->first();

        return redirect()->route('dungeon.explore.view.floor', [
            'dungeon'    => $dungeon,
            'floorIndex' => optional($defaultFloor)->index ?? '1',
        ]);
    }

    /**
     * @param Request                    $request
     * @param MapContextServiceInterface $mapContextService
     * @param Dungeon                    $dungeon
     * @param string                     $floorIndex
     *
     * @return Application|Factory|View|RedirectResponse
     */
    public function viewDungeonFloor(
        Request                    $request,
        MapContextServiceInterface $mapContextService,
        Dungeon                    $dungeon,
        string                     $floorIndex = '1')
    {
        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)->where('index', $floorIndex)->first();

        if ($floor === null) {
            /** @var Floor $defaultFloor */
            $defaultFloor = Floor::where('dungeon_id', $dungeon->id)->where('default', true)->first();

            return redirect()->route('dungeon.explore.view.floor', [
                'dungeon'    => $dungeon,
                'floorIndex' => optional($defaultFloor)->index ?? '1',
            ]);
        } else {
            return view('dungeon.explore.view', [
                'dungeon'    => $dungeon,
                'floor'      => $floor,
                'title'      => __($dungeon->name),
                'mapContext' => $mapContextService->createMapContextDungeonExplore($dungeon, $floor, $dungeon->currentMappingVersion)
            ]);
        }
    }
}
