<?php

namespace App\Http\Controllers\Speedrun;

use App\Http\Controllers\Controller;
use App\Http\Requests\Speedrun\DungeonSpeedrunRequiredNpcsFormRequest;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use App\Service\Npc\NpcServiceInterface;
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
     * @return Application|Factory|View
     */
    public function create(NpcServiceInterface $npcService, Request $request, Dungeon $dungeon, Floor $floor, int $difficulty): \Illuminate\View\View
    {
        $npcs = $npcService->getNpcsForDropdown($dungeon, true)->toArray();

        return view('admin.dungeonspeedrunrequirednpc.new', [
            'dungeon'            => $dungeon,
            'floor'              => $floor,
            'npcIds'             => $npcs,
            'npcIdsWithNullable' => ['-1' => __('controller.dungeonspeedrunrequirednpcs.no_linked_npc')] + $npcs,
            'difficulty'         => $difficulty,
        ]);
    }

    public function createSave(DungeonSpeedrunRequiredNpcsFormRequest $request, Dungeon $dungeon, Floor $floor, int $difficulty): RedirectResponse
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

    public function delete(Request $request, Dungeon $dungeon, Floor $floor, int $difficulty, DungeonSpeedrunRequiredNpc $dungeonspeedrunrequirednpc): RedirectResponse
    {
        try {
            $dungeonspeedrunrequirednpc->delete();
        } catch (Exception) {
            abort(500);
        }

        Session::flash('status', __('controller.dungeonspeedrunrequirednpcs.flash.npc_deleted_successfully'));

        return redirect()->route('admin.floor.edit', ['dungeon' => $dungeon, 'floor' => $floor]);
    }
}
