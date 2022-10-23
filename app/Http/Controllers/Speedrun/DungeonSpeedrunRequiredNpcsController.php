<?php

namespace App\Http\Controllers\Speedrun;

use App\Http\Controllers\Controller;
use App\Http\Requests\Speedrun\DungeonSpeedrunRequiredNpcsFormRequest;
use App\Models\Dungeon;
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
     * @return Application|Factory|View
     */
    public function new(Request $request, Dungeon $dungeon)
    {

        $npcIds = Npc::whereIn('dungeon_id', [-1, $dungeon->id])
            ->get(['name', 'id'])
            ->pluck('name', 'id')
            ->mapWithKeys(function ($name, $id) {
                return [$id => sprintf('%s (%d)', $name, $id)];
            })
            ->toArray();

        return view('admin.dungeonspeedrunrequirednpc.new', [
            'dungeon' => $dungeon,
            'npcIds'  => $npcIds,
        ]);
    }

    /**
     * @param DungeonSpeedrunRequiredNpcsFormRequest $request
     * @param Dungeon $dungeon
     * @return RedirectResponse
     */
    public function savenew(DungeonSpeedrunRequiredNpcsFormRequest $request, Dungeon $dungeon)
    {
        DungeonSpeedrunRequiredNpc::create($request->validated());

        Session::flash('status', __('controller.dungeonspeedrunrequirednpcs.flash.npc_added_successfully'));

        return redirect()->route('admin.dungeon.edit', ['dungeon' => $dungeon]);
    }

    /**
     * @param Request $request
     * @param Dungeon $dungeon
     * @param DungeonSpeedrunRequiredNpc $dungeonspeedrunrequirednpc
     * @return RedirectResponse
     */
    public function delete(Request $request, Dungeon $dungeon, DungeonSpeedrunRequiredNpc $dungeonspeedrunrequirednpc)
    {
        try {
            $dungeonspeedrunrequirednpc->delete();
        } catch (Exception $ex) {
            abort(500);
        }

        Session::flash('status', __('controller.dungeonspeedrunrequirednpcs.flash.npc_deleted_successfully'));

        return redirect()->route('admin.dungeon.edit', ['dungeon' => $dungeon]);
    }
}
