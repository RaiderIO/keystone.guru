<?php

namespace App\Http\Controllers\Speedrun;

use App\Http\Controllers\Controller;
use App\Http\Requests\Speedrun\DungeonSpeedrunRequiredNpcsFormRequest;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpcNpc;
use App\Service\Npc\NpcServiceInterface;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Session;

class DungeonSpeedrunRequiredNpcsController extends Controller
{
    /**
     * @return View
     */
    public function create(
        NpcServiceInterface $npcService,
        Request             $request,
        Dungeon             $dungeon,
        Floor               $floor,
        int                 $difficulty,
    ): View {
        $npcs = $npcService->getNpcsForDropdown(collect([$dungeon]))->toArray();

        return view('admin.dungeonspeedrunrequirednpc.new', [
            'dungeon'            => $dungeon,
            'floor'              => $floor,
            'npcIds'             => $npcs,
            'npcIdsWithNullable' => ['-1' => __('controller.dungeonspeedrunrequirednpcs.no_linked_npc')] + $npcs,
            'difficulty'         => $difficulty,
        ]);
    }

    public function createSave(
        DungeonSpeedrunRequiredNpcsFormRequest $request,
        Dungeon                                $dungeon,
        Floor                                  $floor,
        int                                    $difficulty,
    ): RedirectResponse {
        $validated = $request->validated();

        $parent = DungeonSpeedrunRequiredNpc::create([
            'floor_id'   => $validated['floor_id'],
            'difficulty' => $validated['difficulty'],
            'count'      => $validated['count'],
        ]);

        $npcIds = array_filter([
            $validated['npc_id'],
            (int)$validated['npc2_id'] === -1 ? null : $validated['npc2_id'],
            (int)$validated['npc3_id'] === -1 ? null : $validated['npc3_id'],
            (int)$validated['npc4_id'] === -1 ? null : $validated['npc4_id'],
            (int)$validated['npc5_id'] === -1 ? null : $validated['npc5_id'],
        ]);

        foreach ($npcIds as $npcId) {
            DungeonSpeedrunRequiredNpcNpc::create([
                'dungeon_speedrun_required_npc_id' => $parent->id,
                'npc_id'                           => $npcId,
            ]);
        }

        Session::flash('status', __('controller.dungeonspeedrunrequirednpcs.flash.npc_added_successfully'));

        return redirect()->route('admin.floor.edit', [
            'dungeon' => $dungeon,
            'floor'   => $floor,
        ]);
    }

    public function delete(
        Request                    $request,
        Dungeon                    $dungeon,
        Floor                      $floor,
        int                        $difficulty,
        DungeonSpeedrunRequiredNpc $dungeonspeedrunrequirednpc,
    ): RedirectResponse {
        try {
            $dungeonspeedrunrequirednpc->delete();
        } catch (Exception) {
            abort(500);
        }

        Session::flash('status', __('controller.dungeonspeedrunrequirednpcs.flash.npc_deleted_successfully'));

        return redirect()->route('admin.floor.edit', [
            'dungeon' => $dungeon,
            'floor'   => $floor,
        ]);
    }
}
