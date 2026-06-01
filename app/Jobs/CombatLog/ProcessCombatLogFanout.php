<?php

namespace App\Jobs\CombatLog;

use App\Jobs\Logging\ProcessCombatLogFanoutLoggingInterface;
use App\Service\CombatLog\Dtos\CombatLogRunContextInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessCombatLogFanout implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string                        $s3Bucket,
        private readonly string                        $s3Path,
        private readonly int                           $combatLogVersion,
        private readonly ?CombatLogRunContextInterface $runContext = null,
    ) {
        $this->queue = sprintf('%s-%s-combat-log-fanout', config('app.type'), config('app.env'));
    }

    public function handle(ProcessCombatLogFanoutLoggingInterface $log): void
    {
        $log->handleStart($this->s3Bucket, $this->s3Path, $this->combatLogVersion);

        $files = Storage::disk('s3_combat_logs')->files($this->s3Path);

        foreach ($files as $filePath) {
            $log->handleFileFound($filePath);
            ProcessCombatLogFromS3::dispatch($this->s3Bucket, $filePath, $this->combatLogVersion, runContext: $this->runContext);
        }

        $log->handleEnd(count($files));
    }
}
