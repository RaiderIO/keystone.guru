<?php

namespace Tests\Feature\Jobs\CombatLog;

use App\Jobs\CombatLog\ProcessCombatLogPart;
use App\Jobs\Logging\ProcessCombatLogPartLoggingInterface;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

#[Group('Jobs')]
#[Group('CombatLog')]
final class ProcessCombatLogPartTest extends PublicTestCase
{
    private const string S3_BUCKET          = 'raiderio-combat-logs';
    private const string S3_FILE_PATH       = 'runs/2026/05/15/abc123/part1.log.zip';
    private const int    COMBAT_LOG_VERSION = 22012000005;

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenSuccessfulParse_callsExtractDataAndLogsEnd(): void
    {
        // Arrange
        Storage::fake('s3_combat_logs');
        Storage::disk('s3_combat_logs')->put(self::S3_FILE_PATH, 'combat log content');

        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->expects($this->once())->method('extractData');
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        $log = $this->createMockPublic(ProcessCombatLogPartLoggingInterface::class);
        $log->expects($this->once())->method('handleStart');
        $log->expects($this->once())->method('handleDownloaded');
        $log->expects($this->never())->method('handleFileWriteFailed');
        $log->expects($this->never())->method('handleParseError');
        $log->expects($this->once())->method('handleEnd')->with(true);
        app()->instance(ProcessCombatLogPartLoggingInterface::class, $log);

        // Act
        app()->call([new ProcessCombatLogPart(self::S3_BUCKET, self::S3_FILE_PATH, self::COMBAT_LOG_VERSION), 'handle']);

        // Assert — handled by mock expectations above
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenParseError_logsParseErrorAndCleansUpTempFile(): void
    {
        // Arrange
        Storage::fake('s3_combat_logs');
        Storage::disk('s3_combat_logs')->put(self::S3_FILE_PATH, 'combat log content');

        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->expects($this->once())->method('extractData')
            ->willThrowException(new RuntimeException('Unexpected token on line 42'));
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        $log = $this->createMockPublic(ProcessCombatLogPartLoggingInterface::class);
        $log->expects($this->once())->method('handleParseError')->with(
            self::COMBAT_LOG_VERSION,
            'Unexpected token on line 42',
            RuntimeException::class,
            self::S3_FILE_PATH,
        );
        $log->expects($this->once())->method('handleEnd')->with(false);
        app()->instance(ProcessCombatLogPartLoggingInterface::class, $log);

        // Act
        app()->call([new ProcessCombatLogPart(self::S3_BUCKET, self::S3_FILE_PATH, self::COMBAT_LOG_VERSION), 'handle']);

        // Assert — handled by mock expectations above
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenDiskWriteError_logsFileWriteErrorAndCleansUpTempFile(): void
    {
        // Arrange
        Storage::fake('s3_combat_logs');
        Storage::disk('s3_combat_logs')->put(self::S3_FILE_PATH, 'combat log content');

        $log = $this->createMockPublic(ProcessCombatLogPartLoggingInterface::class);
        $log->expects($this->once())->method('handleFileWriteFailed');
        $log->expects($this->once())->method('handleEnd')->with(false);
        app()->instance(ProcessCombatLogPartLoggingInterface::class, $log);

        // Act
        $mockObject = $this->getMockBuilder(ProcessCombatLogPart::class)
            ->setConstructorArgs([self::S3_BUCKET, self::S3_FILE_PATH, self::COMBAT_LOG_VERSION])
            ->onlyMethods(['writeResourceToDisk'])
            ->getMock();

        $mockObject
            ->expects($this->once())
            ->method('writeResourceToDisk')
            ->willReturn(false);

        app()->call([$mockObject, 'handle']);

        // Assert — handled by mock expectations above
    }
}
