<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Requests\Npc\NpcEnemyForcesFormRequest;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcEnemyForces;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Request;

class NpcEnemyForcesController extends Controller
{
    use ChangesMapping;

    /**
     * Show a page for creating a new npc.
     *
     * @return Factory|\Illuminate\View\View
     */
    public function create(Npc $npc)
    {
        return view('admin.npcenemyforces.edit', [
            'npc' => $npc,
        ]);
    }

    /**
     * @throws Exception
     */
    public function savenew(NpcEnemyForcesFormRequest $request, Npc $npc): RedirectResponse
    {
        // Store it and show the edit page
        $npcEnemyForces = NpcEnemyForces::create($request->validated() + ['npc_id' => $npc->id]);

        // Message to the user
        Session::flash('status', __('view_admin.npcenemyforces.flash.enemy_forces_created'));

        return redirect()->route('admin.npc.npcenemyforces.edit', [
            'npc'            => $npc,
            'npcEnemyForces' => $npcEnemyForces,
        ]);
    }

    /**
     * @return Application|Factory|View
     */
    public function edit(Request $request, Npc $npc, NpcEnemyForces $npcEnemyForces): \Illuminate\View\View
    {
        return view('admin.npcenemyforces.edit', [
            'npc'            => $npc,
            'npcEnemyForces' => $npcEnemyForces,
        ]);
    }

    public function update(
        NpcEnemyForcesFormRequest $request,
        Npc                       $npc,
        NpcEnemyForces            $npcEnemyForces,
    ): \Illuminate\View\View {
        $npcEnemyForces->update($request->validated());

        // Message to the user
        Session::flash('status', __('view_admin.npcenemyforces.flash.enemy_forces_updated'));

        return view('admin.npcenemyforces.edit', [
            'npc'            => $npc,
            'npcEnemyForces' => $npcEnemyForces,
        ]);
    }

    /**
     * @return Application|Factory|View
     */
    public function delete(Request $request, Npc $npc, NpcEnemyForces $npcEnemyForces): RedirectResponse
    {
        $npcEnemyForces->delete();

        // Message to the user
        Session::flash('status', __('view_admin.npcenemyforces.flash.enemy_forces_deleted'));

        return redirect()->route('admin.npc.edit', [
            'npc' => $npc,
        ]);
    }
}
