<?php

namespace App\Jobs\LiveSession;

use App\Models\LiveSession\LiveSession;
use App\Service\LiveSession\LiveSessionBufferProcessingServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessLiveSessionCombatLogBuffer implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public function __construct(public readonly int $liveSessionId)
    {
        $this->queue = sprintf('%s-%s-live-session-process', config('app.type'), config('app.env'));
    }

    /**
     * @return array<int, WithoutOverlapping>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping(sprintf('live-session-buffer-%d', $this->liveSessionId))->dontRelease(),
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(LiveSessionBufferProcessingServiceInterface $service): void
    {
        $liveSession = LiveSession::with(['dungeonRoute.mappingVersion.dungeon.floors', 'combatLogBuffer'])->find($this->liveSessionId);

        if ($liveSession === null || $liveSession->isExpired()) {
            return;
        }

        $service->processBuffer($liveSession);
    }
}
