<?php

namespace App\Http\Controllers\Speedrun;

use App\Http\Controllers\Controller;
use App\Http\Requests\Speedrun\DungeonSpeedrunRequiredNpcsFormRequest;
use App\Models\Dungeon;
use App\Models\Floor;
use App\Models\Npc;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Session;

class DungeonSpeedrunRequiredNpcsController extends Controller
{
    /**
     * @param Request $request
     * @param Dungeon $dungeon
     * @param Floor $floor
     * @return Application|Factory|View
     */
    public function new(Request $request, Dungeon $dungeon, Floor $floor)
    {

        $npcIds = Npc::whereIn('dungeon_id', [-1, $dungeon->id])
            ->get(['name', 'id'])
            ->pluck('name', 'id')
            ->mapWithKeys(function ($name, $id) {
                return [$id => sprintf('%s (%d)', $name, $id)];
            })
            ->toArray();

        return view('admin.dungeonspeedrunrequirednpc.new', [
            'dungeon'            => $dungeon,
            'floor'              => $floor,
            'npcIds'             => $npcIds,
            'npcIdsWithNullable' => ['-1' => __('controller.dungeonspeedrunrequirednpcs.no_linked_npc')] + $npcIds,
        ]);
    }

    /**
     * @param DungeonSpeedrunRequiredNpcsFormRequest $request
     * @param Dungeon $dungeon
     * @param Floor $floor
     * @return RedirectResponse
     */
    public function savenew(DungeonSpeedrunRequiredNpcsFormRequest $request, Dungeon $dungeon, Floor $floor)
    {
        $validated            = $request->validated();
        $validated['npc2_id'] = (int)$validated['npc2_id'] === -1 ? null : $validated['npc2_id'];
        $validated['npc3_id'] = (int)$validated['npc3_id'] === -1 ? null : $validated['npc3_id'];
        $validated['npc4_id'] = (int)$validated['npc4_id'] === -1 ? null : $validated['npc4_id'];
        $validated['npc5_id'] = (int)$validated['npc5_id'] === -1 ? null : $validated['npc5_id'];
        DungeonSpeedrunRequiredNpc::create($validated);

        Session::flash('status', __('controller.dungeonspeedrunrequirednpcs.flash.npc_added_successfully'));

        return redirect()->route('admin.floor.edit', ['dungeon' => $dungeon, 'floor' => $floor]);
    }

    /**
     * @param Request $request
     * @param Dungeon $dungeon
     * @param Floor $floor
     * @param DungeonSpeedrunRequiredNpc $dungeonspeedrunrequirednpc
     * @return RedirectResponse
     */
    public function delete(Request $request, Dungeon $dungeon, Floor $floor, DungeonSpeedrunRequiredNpc $dungeonspeedrunrequirednpc)
    {
        try {
            $dungeonspeedrunrequirednpc->delete();
        } catch (Exception $ex) {
            abort(500);
        }

        Session::flash('status', __('controller.dungeonspeedrunrequirednpcs.flash.npc_deleted_successfully'));

        return redirect()->route('admin.floor.edit', ['dungeon' => $dungeon, 'floor' => $floor]);
    }
}
