<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Combatlog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CombatLog\LiveSession\APILiveSessionCombatLogRequest;
use App\Jobs\LiveSession\ProcessLiveSessionCombatLogBuffer;
use App\Models\LiveSession\LiveSession;
use App\Repositories\Interfaces\LiveSession\LiveSessionCombatLogBatchRepositoryInterface;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class APILiveSessionCombatLogController extends Controller
{
    /**
     * @OA\Post(
     *     operationId="storeLiveSessionCombatLogEvents",
     *     path="/api/v1/combatlog/livesession/{liveSession}/events",
     *     summary="Store a batch of raw filtered combat-log lines for a live session",
     *     description="A batch whose batch_sequence was already accepted for the live session is ignored. Number batches from 0 or 1 up. Batches may arrive out of order: a sequenced batch is processed once every lower batch_sequence has arrived, or once it has waited keystoneguru.live_sessions.combat_log.sequence_gap_grace_seconds (default 30) for a missing one, which is then skipped; a batch arriving after its gap was skipped is still processed, but too late to count kills that depended on it. Unsequenced batches are processed straight away. Compare highest_sequence with the sequences sent so far to detect gaps.",
     *     tags={"CombatLog"},
     *
     *     @OA\Parameter(name="liveSession", in="path", required=true, @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lines"},
     *             @OA\Property(property="lines", type="array", minItems=1, maxItems=5000, description="The caps are the defaults of keystoneguru.live_sessions.combat_log", @OA\Items(type="string", maxLength=8192)),
     *             @OA\Property(property="batch_sequence", type="integer", minimum=0, nullable=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="duplicate", type="boolean", description="True when this batch_sequence had already been accepted, so the batch was ignored"),
     *             @OA\Property(property="highest_sequence", type="integer", nullable=true, description="The highest batch_sequence accepted so far for this live session")
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Not the owner of the live session, or the live session has expired"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function store(
        APILiveSessionCombatLogRequest               $request,
        LiveSession                                  $liveSession,
        LiveSessionCombatLogBatchRepositoryInterface $liveSessionCombatLogBatchRepository,
    ): JsonResponse {
        /** @var array<int, string> $lines */
        $lines         = $request->validated('lines');
        $batchSequence = $request->validated('batch_sequence');

        $compressedLines = gzencode(implode("\n", $lines), 6);
        if ($compressedLines === false) {
            throw new RuntimeException('Unable to compress the combat log batch');
        }

        $isStored = $liveSessionCombatLogBatchRepository->storeBatch(
            $liveSession,
            $batchSequence === null ? null : (int)$batchSequence,
            $compressedLines,
        );

        // A retried batch may be retrying because this dispatch failed after its lines were stored
        ProcessLiveSessionCombatLogBuffer::dispatch($liveSession->id);

        return response()->json([
            'message'          => __($isStored ? 'controller.api.live_session_combat_log.stored' : 'controller.api.live_session_combat_log.duplicate_batch'),
            'duplicate'        => !$isStored,
            'highest_sequence' => $liveSessionCombatLogBatchRepository->getHighestSequence($liveSession),
        ]);
    }
}
