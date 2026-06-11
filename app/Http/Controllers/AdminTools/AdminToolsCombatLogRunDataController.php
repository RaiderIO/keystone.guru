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
        // Only read the run_id index (covering scan) — avoids touching the 100 KB off-page mediumtext column.
        $seasonStats = DB::connection('combatlog')
            ->table('challenge_mode_run_data')
            ->select([
                DB::raw("SUBSTRING_INDEX(run_id, ' ', 1) AS season"),
                DB::raw('COUNT(*) AS total'),
            ])
            ->groupBy('season')
            ->orderByDesc('season')
            ->get();

        return view('admin.tools.combatlog.rundata', [
            'seasonStats' => $seasonStats,
        ]);
    }

    public function pruneBatch(AdminToolsCombatLogRunDataPruneRequest $request): JsonResponse
    {
        $seasonsToKeep = $request->validated('seasons');
        $batchSize     = 500;

        $placeholders = implode(', ', array_fill(0, count($seasonsToKeep), '?'));

        $ids = ChallengeModeRunData::query()
            ->where('post_body', '!=', '')
            ->whereRaw(sprintf("SUBSTRING_INDEX(run_id, ' ', 1) NOT IN (%s)", $placeholders), $seasonsToKeep)
            ->orderByDesc('id')
            ->limit($batchSize)
            ->pluck('id');

        $pruned = 0;

        if ($ids->isNotEmpty()) {
            $pruned = ChallengeModeRunData::query()
                ->whereIn('id', $ids)
                ->update(['post_body' => '']);
        }

        $remaining = ChallengeModeRunData::query()
            ->where('post_body', '!=', '')
            ->whereRaw(sprintf("SUBSTRING_INDEX(run_id, ' ', 1) NOT IN (%s)", $placeholders), $seasonsToKeep)
            ->count();

        return response()->json([
            'pruned'    => $pruned,
            'remaining' => $remaining,
        ]);
    }
}
