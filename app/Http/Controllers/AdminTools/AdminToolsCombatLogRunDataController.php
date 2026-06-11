<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminToolsCombatLogRunDataPruneRequest;
use App\Models\CombatLog\ChallengeModeRunData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminToolsCombatLogRunDataController extends Controller
{
    public function index(): View
    {
        $combatlog = DB::connection('combatlog')->table('challenge_mode_run_data');

        // Only read the run_id index (covering scan) — avoids touching the 100 KB off-page mediumtext column.
        $seasonStats = (clone $combatlog)
            ->select([
                DB::raw("SUBSTRING_INDEX(run_id, ' ', 1) AS season"),
                DB::raw('COUNT(*) AS total'),
            ])
            ->where(function ($q) {
                $q->whereNotNull('post_body');
            })
            ->groupBy('season')
            ->orderByDesc('season')
            ->get();

        $idRange = (clone $combatlog)->selectRaw('MIN(id) AS min_id, MAX(id) AS max_id')->first();

        return view('admin.tools.combatlog.rundata', [
            'seasonStats' => $seasonStats,
            'minId'       => (int)($idRange->min_id ?? 0),
            'maxId'       => (int)($idRange->max_id ?? 0),
        ]);
    }

    public function pruneBatch(AdminToolsCombatLogRunDataPruneRequest $request): JsonResponse
    {
        $seasonsToKeep = $request->validated('seasons');
        $minId         = $request->validated('min_id');
        $maxId         = $request->validated('max_id');

        $placeholders = implode(', ', array_fill(0, count($seasonsToKeep), '?'));

        $ids = ChallengeModeRunData::query()
            ->whereBetween('id', [$minId, $maxId])
            ->where(function ($q) {
                $q->whereNull('post_body')->orWhere('post_body', '!=', '');
            })
            ->whereRaw(sprintf("SUBSTRING_INDEX(run_id, ' ', 1) NOT IN (%s)", $placeholders), $seasonsToKeep)
            ->pluck('id');

        $pruned = 0;

        if ($ids->isNotEmpty()) {
            $pruned = ChallengeModeRunData::query()
                ->whereIn('id', $ids)
                ->update(['post_body' => null]);
        }

        return response()->json(['pruned' => $pruned]);
    }
}
