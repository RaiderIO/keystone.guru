<?php

namespace App\Jobs\CombatLog;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCombatLogPart implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    public function __construct(
        private readonly string $s3Bucket,
        private readonly string $s3FilePath,
        private readonly int    $combatLogVersion,
    ) {
        $this->queue = sprintf('%s-%s-combat-log-process', config('app.type'), config('app.env'));
    }

    public function handle(): void
    {
        // #3178: Download $this->s3FilePath from $this->s3Bucket and process it
    }
}
