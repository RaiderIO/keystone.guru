<?php

namespace App\Service\LiveSession\Logging;

use App\Logging\StructuredLogging;
use Throwable;

class LiveSessionStateServiceLogging extends StructuredLogging implements LiveSessionStateServiceLoggingInterface
{
    public function broadcastStateIfChangedUnchanged(int $liveSessionId, string $hash): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function broadcastSent(int $liveSessionId, string $hash): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function broadcastFailed(int $liveSessionId, Throwable $throwable): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }
}
