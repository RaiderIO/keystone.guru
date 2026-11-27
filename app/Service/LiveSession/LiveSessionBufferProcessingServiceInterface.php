<?php

namespace App\Service\LiveSession;

use App\Models\LiveSession\LiveSession;
use App\Service\LiveSession\Dtos\LiveSessionBufferProcessingResult;

interface LiveSessionBufferProcessingServiceInterface
{
    /**
     * Replay the session's reduced checkpoint followed by its pending combat log batches in one pass, persist the
     * killed/in-combat enemies and the latest player positions into the live-session state tables, then replace
     * the checkpoint with the lines that pass kept and consume the batches. Sequenced batches behind a missing sequence
     * number stay pending until it arrives or its grace period lapses. Every pass, also one with nothing to consume,
     * broadcasts the state when it differs from the last one delivered, so a pass after a failed broadcast delivers it.
     * A pass that cannot write the combat log it parses throws and consumes nothing.
     */
    public function processBuffer(LiveSession $liveSession): LiveSessionBufferProcessingResult;
}
