<?php

namespace App\Jobs\CombatLog;

use App\Jobs\Logging\ProcessCombatLogPartLoggingInterface;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogRunContextInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessCombatLogPart implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    public function __construct(
        private readonly string                        $s3Bucket,
        private readonly string                        $s3FilePath,
        private readonly int                           $combatLogVersion,
        private readonly string                        $diskName = 's3_combat_logs',
        private readonly ?CombatLogRunContextInterface $runContext = null,
    ) {
        $this->queue = sprintf('%s-%s-combat-log-process', config('app.type'), config('app.env'));
    }

    public function handle(
        ProcessCombatLogPartLoggingInterface    $log,
        CombatLogDataExtractionServiceInterface $extractionService,
    ): void {
        $log->handleStart($this->s3Bucket, $this->s3FilePath, $this->combatLogVersion);

        $tempPath = sprintf('%s/%s', sys_get_temp_dir(), basename($this->s3FilePath));
        $resource = null;
        $result   = false;

        try {
            $resource = Storage::disk($this->diskName)->readStream($this->s3FilePath);
            if ($this->writeResourceToDisk($resource, $tempPath) !== false) {
                $log->handleDownloaded($tempPath);

                $extractionService->extractData($tempPath, runContext: $this->runContext);
                $result = true;
            } else {
                $log->handleFileWriteFailed($tempPath);
            }
        } catch (\Throwable $e) {
            $log->handleParseError($this->combatLogVersion, $e->getMessage(), $e::class, $this->s3FilePath);
        } finally {
            if (is_resource($resource)) {
                fclose($resource);
            }
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            // Clean up the extracted combat log too
            $txtFilePath = str_replace('zip', 'txt', $tempPath);
            if (file_exists($txtFilePath)) {
                unlink($txtFilePath);
            }
            $log->handleEnd($result);
        }
    }

    public function writeResourceToDisk($resource, $destination): int|false
    {
        return file_put_contents($destination, $resource);
    }
}
