<?php

namespace App\Jobs\CombatLog;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCombatLogFanout implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $s3Bucket,
        private readonly string $s3Path,
        private readonly int    $combatLogVersion,
    ) {
        $this->queue = 'combat-log-fanout';
    }

    public function handle(): void
    {
        // #3177: List files in $this->s3Path within $this->s3Bucket and dispatch one ProcessCombatLogPart per file
    }
}
