<?php

namespace App\Jobs\LiveSession;

use App\Jobs\Enums\QueueName;
use App\Models\LiveSession\LiveSession;
use App\Service\LiveSession\LiveSessionBufferProcessingServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Throwable;

class ProcessLiveSessionCombatLogBuffer implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const int RELEASE_AFTER_SECONDS = 5;

    public int $timeout = 120;

    /** A genuine failure is not retried; only releases from the overlap lock are, until retryUntil(). */
    public int $maxExceptions = 1;

    public function __construct(public readonly int $liveSessionId)
    {
        $this->queue = QueueName::LiveSessionProcess->queueName();
    }

    /**
     * @return array<int, WithoutOverlapping>
     */
    public function middleware(): array
    {
        // An overlapping job is released rather than dropped: the running job may have read the buffer before
        // this job's lines were appended. The lock expires shortly after the job's own timeout so a worker
        // killed mid-job cannot hold it forever
        return [
            new WithoutOverlapping(sprintf('live-session-buffer-%d', $this->liveSessionId))
                ->releaseAfter(self::RELEASE_AFTER_SECONDS)
                ->expireAfter($this->timeout + 30),
        ];
    }

    /**
     * Released jobs count as attempts, so the retry window is bounded by time instead: long enough to outlast
     * an expired lock left by a killed worker.
     */
    public function retryUntil(): Carbon
    {
        return Carbon::now()->addSeconds(($this->timeout + 30) * 2);
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
