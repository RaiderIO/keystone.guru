<?php

namespace App\Service\LiveSession\Dtos;

use Illuminate\Support\Carbon;

/**
 * What a pass over a live session's combat log buffer left for a later pass to do.
 */
class LiveSessionBufferProcessingResult
{
    /**
     * @param Carbon|null $heldUntil         When the batches held back behind a missing sequence number may skip it; null
     *                                       when none are
     * @param bool        $isBroadcastFailed Whether the state could not be delivered to the session's viewers
     */
    public function __construct(
        public readonly ?Carbon $heldUntil,
        public readonly bool    $isBroadcastFailed,
    ) {
    }
}
