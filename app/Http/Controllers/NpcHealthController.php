<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Requests\Npc\NpcHealthFormRequest;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcHealth;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Request;

class NpcHealthController extends Controller
{
    use ChangesMapping;

    /**
     * Show a page for creating a new npc.
     *
     * @return Factory|\Illuminate\View\View
     */
    public function create(Npc $npc)
    {
        return view('admin.npchealth.edit', [
            'npc'                    => $npc,
            'npcHealthsAutoComplete' => Npc::with('classification')
                ->selectRaw('npcs.*')
                ->join('npc_dungeons', 'npc_dungeons.npc_id', '=', 'npcs.id')
                ->whereIn('npc_dungeons.dungeon_id', $npc->dungeons->pluck('id')->toArray())
                ->orderBy('npcs.id')
                ->get(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function savenew(NpcHealthFormRequest $request, Npc $npc): RedirectResponse
    {
        // Store it and show the edit page
        $npcHealth = NpcHealth::create($request->validated() + ['npc_id' => $npc->id]);

        // Message to the user
        Session::flash('status', __('view_admin.npchealth.flash.npc_health_created'));

        return redirect()->route('admin.npc.npchealth.edit', [
            'npc'       => $npc,
            'npcHealth' => $npcHealth,
        ]);
    }

    /**
     * @return Application|Factory|View
     */
    public function edit(Request $request, Npc $npc, NpcHealth $npcHealth): \Illuminate\View\View
    {
        return view('admin.npchealth.edit', [
            'npc'                    => $npc,
            'npcHealth'              => $npcHealth,
            'npcHealthsAutoComplete' => Npc::with('classification')
                ->selectRaw('npcs.*')
                ->join('npc_dungeons', 'npc_dungeons.npc_id', '=', 'npcs.id')
                ->whereIn('npc_dungeons.dungeon_id', $npc->dungeons->pluck('id')->toArray())
                ->orderBy('npcs.id')
                ->get(),
        ]);
    }

    public function update(NpcHealthFormRequest $request, Npc $npc, NpcHealth $npcHealth): RedirectResponse
    {
        $npcHealth->update($request->validated());

        // Message to the user
        Session::flash('status', __('view_admin.npchealth.flash.npc_health_updated'));

        return redirect()->route('admin.npc.npchealth.edit', [
            'npc'       => $npc,
            'npcHealth' => $npcHealth,
        ]);
    }

    /**
     * @return Application|Factory|View
     */
    public function delete(Request $request, Npc $npc, NpcHealth $npcHealth): RedirectResponse
    {
        $npcHealth->delete();

        // Message to the user
        Session::flash('status', __('view_admin.npchealth.flash.npc_health_deleted'));

        return redirect()->route('admin.npc.edit', [
            'npc' => $npc,
        ]);
    }
}
