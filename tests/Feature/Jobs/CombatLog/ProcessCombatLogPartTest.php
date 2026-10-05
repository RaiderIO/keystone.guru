<?php

namespace Tests\Feature\Jobs\CombatLog;

use App\Jobs\CombatLog\ProcessCombatLogFromS3;
use App\Jobs\Logging\ProcessCombatLogPartLoggingInterface;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogRunContext;
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

        $runContext = new CombatLogRunContext(keyLevel: 10, affixIds: [9, 10]);

        $extractedContents = null;
        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->expects($this->once())
            ->method('extractData')
            ->with(self::tempPath(), null, null, $this->identicalTo($runContext))
            ->willReturnCallback(static function (string $filePath) use (&$extractedContents): null {
                $extractedContents = file_get_contents($filePath);
                // Stands in for the unzipped log the extraction leaves next to the archive
                file_put_contents(self::tempTxtPath(), 'unzipped combat log content');

                return null;
            });
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        $log = $this->createMockPublic(ProcessCombatLogPartLoggingInterface::class);
        $log->expects($this->once())->method('handleStart')->with(self::S3_BUCKET, self::S3_FILE_PATH, self::COMBAT_LOG_VERSION);
        $log->expects($this->once())->method('handleDownloaded')->with(self::tempPath());
        $log->expects($this->never())->method('handleFileWriteFailed');
        $log->expects($this->never())->method('handleParseError');
        $log->expects($this->once())->method('handleEnd')->with(true);
        app()->instance(ProcessCombatLogPartLoggingInterface::class, $log);

        // Act
        app()->call([new ProcessCombatLogFromS3(self::S3_BUCKET, self::S3_FILE_PATH, self::COMBAT_LOG_VERSION, 's3_combat_logs', $runContext), 'handle']);

        // Assert
        $this->assertSame('combat log content', $extractedContents);
        $this->assertFileDoesNotExist(self::tempPath());
        $this->assertFileDoesNotExist(self::tempTxtPath());
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

        $downloadedFileExisted = false;
        $extractionService     = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->expects($this->once())->method('extractData')
            ->willReturnCallback(static function (string $filePath) use (&$downloadedFileExisted): never {
                $downloadedFileExisted = file_exists($filePath);

                throw new RuntimeException('Unexpected token on line 42');
            });
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
        app()->call([new ProcessCombatLogFromS3(self::S3_BUCKET, self::S3_FILE_PATH, self::COMBAT_LOG_VERSION), 'handle']);

        // Assert
        $this->assertTrue($downloadedFileExisted);
        $this->assertFileDoesNotExist(self::tempPath());
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

        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->expects($this->never())->method('extractData');
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        $log = $this->createMockPublic(ProcessCombatLogPartLoggingInterface::class);
        $log->expects($this->once())->method('handleFileWriteFailed')->with(self::tempPath());
        $log->expects($this->never())->method('handleDownloaded');
        $log->expects($this->never())->method('handleParseError');
        $log->expects($this->once())->method('handleEnd')->with(false);
        app()->instance(ProcessCombatLogPartLoggingInterface::class, $log);

        // Act
        $mockObject = $this->getMockBuilder(ProcessCombatLogFromS3::class)
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

    private static function tempPath(): string
    {
        return sprintf('%s/%s', sys_get_temp_dir(), basename(self::S3_FILE_PATH));
    }

    private static function tempTxtPath(): string
    {
        return str_replace('.zip', '.txt', self::tempPath());
    }
}
