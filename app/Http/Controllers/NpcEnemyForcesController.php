<?php

namespace App\Http\Controllers;


use App\Http\Requests\Npc\NpcEnemyForcesFormRequest;
use App\Models\Npc;
use App\Models\Npc\NpcEnemyForces;
use Request;

class NpcEnemyForcesController extends Controller
{
    /**
     * @param Request $request
     * @param Npc $npc
     * @param NpcEnemyForces $npcEnemyForces
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Request $request, Npc $npc, NpcEnemyForces $npcEnemyForces)
    {
        return view('admin.npcenemyforces.edit', [
            'npc'            => $npc,
            'npcEnemyForces' => $npcEnemyForces,
        ]);
    }


    public function update(NpcEnemyForcesFormRequest $request, Npc $npc, NpcEnemyForces $npcEnemyForces)
    {
        return view('admin.npcenemyforces.edit', [
            'npc'         => $npc,
            'enemyForces' => $npcEnemyForces,
        ]);
    }
}
