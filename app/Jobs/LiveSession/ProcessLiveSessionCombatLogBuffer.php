<?php

namespace App\Jobs\LiveSession;

use App\Jobs\Enums\QueueName;
use App\Models\LiveSession\LiveSession;
use App\Service\LiveSession\Dtos\LiveSessionBufferProcessingResult;
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

    public const int BROADCAST_RETRY_SECONDS = 10;

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
        // Two runs must not overlap: the one committing last would overwrite the other's checkpoint. An overlapping
        // job is released to retry later, as the running job may have read the pending batches before the batch that
        // dispatched this job was stored. The lock expires shortly after the job's own timeout so a worker killed
        // mid-job cannot hold it forever
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
     * Batches held back behind a missing sequence number get another pass once their grace period lapses, and a state
     * that could not be broadcast another attempt shortly after, also when the client sends nothing more.
     *
     * @throws Throwable
     */
    public function handle(LiveSessionBufferProcessingServiceInterface $service): void
    {
        $liveSession = LiveSession::with(['dungeonRoute.mappingVersion.dungeon.floors'])->find($this->liveSessionId);

        if ($liveSession === null || $liveSession->isExpired()) {
            return;
        }

        $releaseAt = self::determineReleaseAt($service->processBuffer($liveSession));

        // Not a fresh dispatch: the sync queue ignores its delay and would rerun this job on the spot, in a loop
        if ($releaseAt !== null) {
            $this->release($releaseAt);
        }
    }

    private static function determineReleaseAt(LiveSessionBufferProcessingResult $result): ?Carbon
    {
        $heldBatchesReleaseAt = $result->heldUntil?->copy()->addSecond();
        $broadcastReleaseAt   = $result->isBroadcastFailed ? Carbon::now()->addSeconds(self::BROADCAST_RETRY_SECONDS) : null;

        if ($heldBatchesReleaseAt === null || $broadcastReleaseAt === null) {
            return $heldBatchesReleaseAt ?? $broadcastReleaseAt;
        }

        return $heldBatchesReleaseAt->min($broadcastReleaseAt);
    }
}
