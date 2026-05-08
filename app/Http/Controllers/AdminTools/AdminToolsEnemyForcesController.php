<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Jobs\RefreshEnemyForces;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Npc\Npc;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminToolsEnemyForcesController extends Controller
{
    public function enemyforcesimport(): View
    {
        return view('admin.tools.enemyforces.import');
    }

    public function enemyforcesimportsubmit(Request $request): void
    {
        $json = json_decode((string)$request->get('import_string'), true);

        $results = [];
        foreach ($json['Npcs'] as $jsonNpc) {
            $npc = Npc::where('id', $jsonNpc['Id'])->first();

            if ($npc !== null) {
                $keyMapping = [
                    'MythicHealth' => 'base_health',
                    'Amount'       => 'enemy_forces',
                ];

                $toUpdate = [];
                foreach ($jsonNpc as $key => $value) {
                    if ($key !== 'Id' && $value >= 0 && isset($keyMapping[$key])) {
                        $toUpdate[$keyMapping[$key]] = $value;
                    }
                }

                $npc->update($toUpdate);

                $results[] = sprintf('Changed npc %d fields: %s', $jsonNpc['Id'], json_encode($toUpdate));
            } else {
                $results[] = sprintf('Unable to find npc %d', $jsonNpc['Id']);
            }
        }

        dd($results);
    }

    public function enemyforcesrecalculate(): View
    {
        return view('admin.tools.enemyforces.recalculate');
    }

    public function enemyforcesrecalculatesubmit(Request $request): void
    {
        $dungeonId = (int)$request->get('dungeon_id');

        $builder = DungeonRoute::without([
            'faction',
            'specializations',
            'classes',
            'races',
            'affixes',
        ])
            ->select('id')
            ->when($dungeonId !== -1, static fn(Builder $builder) => $builder->where('dungeon_id', $dungeonId));

        $count = 0;
        foreach ($builder->get() as $dungeonRoute) {
            RefreshEnemyForces::dispatch($dungeonRoute->id);
            $count++;
        }

        dd(sprintf('Dispatched %d jobs', $count));
    }
}
