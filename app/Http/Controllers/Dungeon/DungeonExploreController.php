<?php

namespace App\Http\Controllers\Dungeon;

use App\Http\Controllers\Controller;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Service\MapContext\MapContextServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DungeonExploreController extends Controller
{
    public function list(Request $request): View
    {
        return view('dungeon.explore.list');
    }

    public function viewDungeon(Request $request, Dungeon $dungeon): RedirectResponse
    {
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)
            ->defaultOrFacade()
            ->first();

        return redirect()->route('dungeon.explore.view.floor', [
            'dungeon'    => $dungeon,
            'floorIndex' => $defaultFloor?->index ?? '1',
        ]);
    }

    public function viewDungeonFloor(
        Request                    $request,
        MapContextServiceInterface $mapContextService,
        Dungeon                    $dungeon,
        string                     $floorIndex = '1'): View|RedirectResponse
    {
        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)
            ->indexOrFacade($floorIndex)
            ->first();

        if ($floor === null) {
            /** @var Floor $defaultFloor */
            $defaultFloor = Floor::where('dungeon_id', $dungeon->id)
                ->defaultOrFacade()
                ->first();

            return redirect()->route('dungeon.explore.view.floor', [
                'dungeon'    => $dungeon,
                'floorIndex' => $defaultFloor?->index ?? '1',
            ]);
        } else {
            if ($floor->index !== (int)$floorIndex) {
                return redirect()->route('dungeon.explore.view.floor', [
                    'dungeon'    => $dungeon,
                    'floorIndex' => $floor->index,
                ]);
            }

            return view('dungeon.explore.view', [
                'dungeon'    => $dungeon,
                'floor'      => $floor,
                'title'      => __($dungeon->name),
                'mapContext' => $mapContextService->createMapContextDungeonExplore($dungeon, $floor, $dungeon->currentMappingVersion),
            ]);
        }
    }
}
