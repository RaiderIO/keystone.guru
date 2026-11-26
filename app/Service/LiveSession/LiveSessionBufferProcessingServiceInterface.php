<?php

namespace App\Service\LiveSession;

use App\Models\LiveSession\LiveSession;

interface LiveSessionBufferProcessingServiceInterface
{
    /**
     * Replay the session's reduced checkpoint followed by its pending combat log batches in one pass, persist the
     * killed/in-combat enemies and the latest player positions into the live-session state tables, then replace
     * the checkpoint with the lines that pass kept and consume the batches. Does nothing when no batch is pending.
     */
    public function processBuffer(LiveSession $liveSession): void;
}
