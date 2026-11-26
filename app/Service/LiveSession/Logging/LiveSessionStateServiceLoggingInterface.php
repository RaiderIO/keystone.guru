<?php

namespace App\Service\LiveSession\Logging;

use App\Logging\StructuredLoggingInterface;
use Throwable;

interface LiveSessionStateServiceLoggingInterface extends StructuredLoggingInterface
{
    public function broadcastStateIfChangedUnchanged(int $liveSessionId, string $hash): void;

    public function broadcastSent(int $liveSessionId, string $hash): void;

    public function broadcastFailed(int $liveSessionId, Throwable $throwable): void;

    public function withBroadcastLockTimedOut(int $liveSessionId, int $waitSeconds): void;
}
