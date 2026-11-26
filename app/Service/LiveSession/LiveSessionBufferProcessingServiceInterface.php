<?php

namespace App\Service\LiveSession;

use App\Models\LiveSession\LiveSession;
use Illuminate\Support\Carbon;

interface LiveSessionBufferProcessingServiceInterface
{
    /**
     * Replay the session's reduced checkpoint followed by its pending combat log batches in one pass, persist the
     * killed/in-combat enemies and the latest player positions into the live-session state tables, then replace
     * the checkpoint with the lines that pass kept and consume the batches. Does nothing when no batch is pending.
     * Sequenced batches behind a missing sequence number stay pending until it arrives or its grace period lapses.
     *
     * @return Carbon|null When the batches held back behind a missing sequence number may skip it; null when none are
     */
    public function processBuffer(LiveSession $liveSession): ?Carbon;
}
