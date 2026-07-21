<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Models\CombatLog\CombatLogParseFailure;
use App\Service\RaiderIO\Dtos\CombatLogSegment;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class AdminToolsCombatLogParseFailureController extends Controller
{
    private const int MAX_RESULTS = 500;

    public function index(): View
    {
        $failures = CombatLogParseFailure::query()
            ->orderBy('resolved_at')
            ->orderByDesc('created_at')
            ->limit(self::MAX_RESULTS)
            ->get();

        return view('admin.tools.combatlog.parsefailures', [
            'failures' => $failures,
        ]);
    }

    public function segments(RaiderIOApiServiceInterface $raiderIOApiService, CombatLogParseFailure $parseFailure): JsonResponse
    {
        $season = $parseFailure->season();

        if ($season === null) {
            return response()->json([
                'error' => __('controller.admintools.error.combatlog_parse_failure_no_season'),
            ], 422);
        }

        $segmentsResponse = $raiderIOApiService->getCombatLogSegmentsForRun($season, $parseFailure->run_id);

        if ($segmentsResponse === null || empty($segmentsResponse->segments)) {
            return response()->json([
                'error' => __('controller.admintools.error.combatlog_parse_failure_no_segments'),
            ], 404);
        }

        return response()->json([
            'segments' => array_map(static fn(CombatLogSegment $segment): array => [
                'id'          => $segment->id,
                'type'        => $segment->type,
                'downloadUrl' => $segment->downloadUrl,
            ], $segmentsResponse->segments),
        ]);
    }

    public function resolve(CombatLogParseFailure $parseFailure): RedirectResponse
    {
        $parseFailure->update(['resolved_at' => now()]);

        Session::flash('status', __('controller.admintools.flash.combatlog_parse_failure_resolved'));

        return redirect()->route('admin.tools.combatlog.parsefailures.view');
    }
}
