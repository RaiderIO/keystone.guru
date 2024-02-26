<?php

namespace App\Http\Controllers;

use App\Http\Requests\Npc\NpcEnemyForcesFormRequest;
use App\Models\Npc;
use App\Models\Npc\NpcEnemyForces;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Request;

class NpcEnemyForcesController extends Controller
{
    /**
     * @return Application|Factory|View
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
        $npcEnemyForces->update($request->validated());

        return view('admin.npcenemyforces.edit', [
            'npc'            => $npc,
            'npcEnemyForces' => $npcEnemyForces,
        ]);
    }
}
