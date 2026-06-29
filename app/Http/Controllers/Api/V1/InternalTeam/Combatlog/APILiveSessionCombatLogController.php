<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Combatlog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CombatLog\LiveSession\APILiveSessionCombatLogRequest;
use App\Jobs\LiveSession\ProcessLiveSessionCombatLogBuffer;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBuffer;
use Illuminate\Http\JsonResponse;

class APILiveSessionCombatLogController extends Controller
{
    /**
     * @OA\Post(
     *     operationId="storeLiveSessionCombatLogEvents",
     *     path="/api/v1/combatlog/livesession/{liveSession}/events",
     *     summary="Append a batch of raw filtered combat-log lines to a live session buffer",
     *     tags={"CombatLog"},
     *
     *     @OA\Parameter(name="liveSession", in="path", required=true, @OA\Schema(type="string")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="lines", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="batch_sequence", type="integer", nullable=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Successful operation",
     *         @OA\JsonContent(@OA\Property(property="message", type="string"))
     *     )
     * )
     */
    public function store(
        APILiveSessionCombatLogRequest $request,
        LiveSession                    $liveSession,
    ): JsonResponse {
        $validated     = $request->validated();
        $lines         = $validated['lines'];
        $batchSequence = $validated['batch_sequence'] ?? null;

        /** @var LiveSessionCombatLogBuffer $buffer */
        $buffer = LiveSessionCombatLogBuffer::query()
            ->firstOrNew(['live_session_id' => $liveSession->id]);

        if ($batchSequence !== null && $buffer->last_sequence !== null && $batchSequence <= $buffer->last_sequence) {
            return response()->json(['message' => __('controller.api.live_session_combat_log.duplicate_batch')]);
        }

        $existingLines = [];
        if ($buffer->buffer !== null) {
            $decoded = gzdecode($buffer->buffer);
            if ($decoded !== false && $decoded !== '') {
                $existingLines = explode("\n", $decoded);
            }
        }

        $allLines   = array_merge($existingLines, $lines);
        $compressed = gzencode(implode("\n", $allLines), 6);

        $buffer->live_session_id = $liveSession->id;
        $buffer->buffer          = $compressed !== false ? $compressed : null;
        if ($batchSequence !== null) {
            $buffer->last_sequence = $batchSequence;
        }
        $buffer->save();

        ProcessLiveSessionCombatLogBuffer::dispatch($liveSession->id);

        return response()->json(['message' => __('controller.api.live_session_combat_log.stored')]);
    }
}
