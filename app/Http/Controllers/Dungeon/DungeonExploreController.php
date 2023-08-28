<?php

namespace App\Http\Controllers\Dungeon;

use App\Http\Controllers\Controller;
use App\Logic\MapContext\MapContextDungeonExplore;
use App\Logic\MapContext\MapContextDungeonRoute;
use App\Models\Dungeon;
use App\Models\Floor;
use Illuminate\Http\Request;
use Psr\SimpleCache\InvalidArgumentException;

class DungeonExploreController extends Controller
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function list(Request $request)
    {
        return view('dungeon.explore.list');
    }

    /**
     * @param Request $request
     * @param Dungeon $dungeon
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
     * @param Request $request
     * @param Dungeon $dungeon
     * @param string  $floorIndex
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function viewDungeonFloor(Request $request, Dungeon $dungeon, string $floorIndex = '1')
    {
        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        /** @var Floor $defaultFloor */
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
                'mapContext' => new MapContextDungeonExplore($dungeon, $floor, $dungeon->getCurrentMappingVersion())
            ]);
        }
    }
}
