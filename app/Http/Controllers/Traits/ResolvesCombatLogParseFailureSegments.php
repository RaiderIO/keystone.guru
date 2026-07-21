<?php

namespace App\Http\Controllers\Traits;

use App\Models\CombatLog\CombatLogParseFailure;
use App\Service\RaiderIO\Dtos\CombatLogSegment;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use Illuminate\Http\JsonResponse;

trait ResolvesCombatLogParseFailureSegments
{
    /**
     * Looks up the Raider.IO log segment download URLs for a parse failure's run.
     */
    public function resolveCombatLogParseFailureSegments(
        RaiderIOApiServiceInterface $raiderIOApiService,
        CombatLogParseFailure       $parseFailure,
    ): JsonResponse {
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
}
