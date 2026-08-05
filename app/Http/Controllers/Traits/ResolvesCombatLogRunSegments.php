<?php

namespace App\Http\Controllers\Traits;

use App\Models\Season;
use App\Service\RaiderIO\Dtos\CombatLogSegment;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use Illuminate\Http\JsonResponse;

trait ResolvesCombatLogRunSegments
{
    /**
     * Looks up the Raider.IO log segment download URLs for a run.
     */
    public function resolveCombatLogRunSegments(
        RaiderIOApiServiceInterface $raiderIOApiService,
        Season                      $season,
        int                         $runId,
    ): JsonResponse {
        $segmentsResponse = $raiderIOApiService->getCombatLogSegmentsForRun($season, $runId);

        if ($segmentsResponse === null || empty($segmentsResponse->segments)) {
            return response()->json([
                'error' => __('controller.apicombatlogrun.error.no_segments'),
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
