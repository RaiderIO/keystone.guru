<?php

namespace App\Service\LiveSession;

use App\Models\LiveSession\LiveSession;

interface LiveSessionBufferProcessingServiceInterface
{
    /**
     * Decompress and re-run ARC over the session's accumulated combat log buffer, then diff and
     * persist newly-killed enemies and latest player positions into the live-session state tables.
     */
    public function processBuffer(LiveSession $liveSession): void;
}
