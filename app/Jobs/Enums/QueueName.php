<?php

namespace App\Jobs\Enums;

/**
 * Every queue jobs are dispatched to. AWS runs one SQS queue + ECS worker per case and stage
 * (keystoneguru-infra `QUEUE_WORKER_CONFIGS`); a new case needs a matching queue there.
 */
enum QueueName: string
{
    /** The connection's fallback queue, for jobs that never set a queue themselves */
    case Default = 'default';

    case LongRunning = 'long-running';

    case Thumbnail = 'thumbnail';

    case ThumbnailApi = 'thumbnail-api';

    case CombatLogProcess = 'cl-process';

    /**
     * The full queue name for the current stage, e.g. `production-thumbnail`.
     */
    public function queueName(): string
    {
        return sprintf('%s-%s', config('app.type'), $this->value);
    }
}
