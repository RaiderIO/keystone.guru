<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Jobs\RefreshEnemyForces;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminToolsEnemyForcesController extends Controller
{
    public function enemyforcesrecalculate(): View
    {
        return view('admin.tools.enemyforces.recalculate');
    }

    public function enemyforcesrecalculatesubmit(Request $request): void
    {
        $dungeonId = (int)$request->get('dungeon_id');

        $builder = DungeonRoute::query()
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
