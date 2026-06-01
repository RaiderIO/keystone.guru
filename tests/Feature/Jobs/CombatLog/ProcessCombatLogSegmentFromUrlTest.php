<?php

namespace Tests\Feature\Jobs\CombatLog;

use App\Jobs\CombatLog\ProcessCombatLogSegmentFromUrl;
use App\Jobs\Logging\ProcessCombatLogSegmentFromUrlLoggingInterface;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

#[Group('Jobs')]
#[Group('CombatLog')]
final class ProcessCombatLogSegmentFromUrlTest extends PublicTestCase
{
    private const int    RUN_ID             = 42;
    private const int    SEGMENT_ID         = 1;
    private const int    COMBAT_LOG_VERSION = 22012000005;
    private const string DOWNLOAD_URL       = 'https://raider.io/segments/42/1.txt';

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenSuccessfulDownload_callsExtractDataAndLogsEnd(): void
    {
        // Arrange
        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->expects($this->once())->method('extractData');
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        $log = $this->createMockPublic(ProcessCombatLogSegmentFromUrlLoggingInterface::class);
        $log->expects($this->once())->method('handleStart');
        $log->expects($this->never())->method('handleDownloadFailed');
        $log->expects($this->once())->method('handleDownloaded');
        $log->expects($this->never())->method('handleParseError');
        $log->expects($this->once())->method('handleEnd')->with(self::RUN_ID, true);
        app()->instance(ProcessCombatLogSegmentFromUrlLoggingInterface::class, $log);

        $job = $this->getMockBuilder(ProcessCombatLogSegmentFromUrl::class)
            ->setConstructorArgs([self::RUN_ID, self::SEGMENT_ID, self::DOWNLOAD_URL, self::COMBAT_LOG_VERSION])
            ->onlyMethods(['curlSaveToFile'])
            ->getMock();

        $job->expects($this->once())
            ->method('curlSaveToFile')
            ->willReturnCallback(function (string $url, string $tempPath): bool {
                file_put_contents($tempPath, sprintf('content from %s', $url));

                return true;
            });

        // Act
        app()->call([$job, 'handle']);

        // Assert — handled by mock expectations above
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenDownloadFails_throwsRuntimeException(): void
    {
        // Arrange
        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->expects($this->never())->method('extractData');
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        $log = $this->createMockPublic(ProcessCombatLogSegmentFromUrlLoggingInterface::class);
        $log->expects($this->once())->method('handleDownloadFailed');
        $log->expects($this->once())->method('handleEnd')->with(self::RUN_ID, false);
        app()->instance(ProcessCombatLogSegmentFromUrlLoggingInterface::class, $log);

        $job = $this->getMockBuilder(ProcessCombatLogSegmentFromUrl::class)
            ->setConstructorArgs([self::RUN_ID, self::SEGMENT_ID, self::DOWNLOAD_URL, self::COMBAT_LOG_VERSION])
            ->onlyMethods(['curlSaveToFile'])
            ->getMock();

        $job->expects($this->once())
            ->method('curlSaveToFile')
            ->willReturn(false);

        // Assert + Act
        $this->expectException(RuntimeException::class);
        app()->call([$job, 'handle']);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenParseError_logsParseErrorAndCleansUpTempFile(): void
    {
        // Arrange
        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->expects($this->once())
            ->method('extractData')
            ->willThrowException(new \Exception('Unexpected token on line 42'));
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        $log = $this->createMockPublic(ProcessCombatLogSegmentFromUrlLoggingInterface::class);
        $log->expects($this->once())->method('handleParseError')->with(
            self::RUN_ID,
            self::COMBAT_LOG_VERSION,
            'Unexpected token on line 42',
            \Exception::class,
        );
        $log->expects($this->once())->method('handleEnd')->with(self::RUN_ID, false);
        app()->instance(ProcessCombatLogSegmentFromUrlLoggingInterface::class, $log);

        $job = $this->getMockBuilder(ProcessCombatLogSegmentFromUrl::class)
            ->setConstructorArgs([self::RUN_ID, self::SEGMENT_ID, self::DOWNLOAD_URL, self::COMBAT_LOG_VERSION])
            ->onlyMethods(['curlSaveToFile'])
            ->getMock();

        $tempFilePath = null;
        $job->expects($this->once())
            ->method('curlSaveToFile')
            ->willReturnCallback(function (string $url, string $tempPath) use (&$tempFilePath): bool {
                file_put_contents($tempPath, 'content');
                $tempFilePath = $tempPath;

                return true;
            });

        // Act
        app()->call([$job, 'handle']);

        // Assert — temp file cleaned up
        if ($tempFilePath !== null) {
            $this->assertFileDoesNotExist($tempFilePath);
        }
    }
}
