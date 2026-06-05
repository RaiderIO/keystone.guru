<?php

namespace Tests\Feature\Jobs\CombatLog;

use App\Jobs\CombatLog\ProcessCombatLogFanout;
use App\Jobs\CombatLog\ProcessCombatLogFromS3;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Jobs')]
#[Group('CombatLog')]
final class ProcessCombatLogFromS3Test extends PublicTestCase
{
    private const string S3_BUCKET          = 'raiderio-combat-logs';
    private const string S3_PATH            = 'runs/2026/05/15/abc123';
    private const int    COMBAT_LOG_VERSION = 22012000005;

    #[Test]
    public function handle_givenThreeFilesInS3Folder_dispatchesThreeProcessCombatLogPartJobs(): void
    {
        // Arrange
        Bus::fake();
        Storage::fake('s3_combat_logs');
        Storage::disk('s3_combat_logs')->put(sprintf('%s/part1.log.zip', self::S3_PATH), 'content');
        Storage::disk('s3_combat_logs')->put(sprintf('%s/part2.log.zip', self::S3_PATH), 'content');
        Storage::disk('s3_combat_logs')->put(sprintf('%s/part3.log.zip', self::S3_PATH), 'content');

        // Act
        app()->call([new ProcessCombatLogFanout(self::S3_BUCKET, self::S3_PATH, self::COMBAT_LOG_VERSION), 'handle']);

        // Assert
        Bus::assertDispatchedTimes(ProcessCombatLogFromS3::class, 3);
        Bus::assertDispatched(ProcessCombatLogFromS3::class, function (ProcessCombatLogFromS3 $job): bool {
            $bucket   = new \ReflectionProperty($job, 's3Bucket')->getValue($job);
            $filePath = new \ReflectionProperty($job, 's3FilePath')->getValue($job);

            return $bucket === self::S3_BUCKET && str_starts_with($filePath, self::S3_PATH . '/');
        });
    }

    #[Test]
    public function handle_givenEmptyS3Folder_dispatchesNoJobs(): void
    {
        // Arrange
        Bus::fake();
        Storage::fake('s3_combat_logs');

        // Act
        app()->call([new ProcessCombatLogFanout(self::S3_BUCKET, self::S3_PATH, self::COMBAT_LOG_VERSION), 'handle']);

        // Assert
        Bus::assertNotDispatched(ProcessCombatLogFromS3::class);
    }
}
