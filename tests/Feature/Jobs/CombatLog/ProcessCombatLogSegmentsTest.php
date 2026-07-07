<?php

namespace Tests\Feature\Jobs\CombatLog;

use App\Jobs\CombatLog\ProcessCombatLogSegments;
use App\Jobs\Logging\ProcessCombatLogSegmentsLoggingInterface;
use App\Models\Season;
use App\Repositories\Interfaces\CombatLog\CombatLogParseFailureRepositoryInterface;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogRunContext;
use App\Service\CombatLog\Exceptions\CombatLogParseException;
use App\Service\RaiderIO\Dtos\CombatLogSegment;
use App\Service\RaiderIO\Dtos\CombatLogSegmentsResponse;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\Exception;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

#[Group('Jobs')]
#[Group('CombatLog')]
final class ProcessCombatLogSegmentsTest extends PublicTestCase
{
    private const int RUN_ID             = 42;
    private const int COMBAT_LOG_VERSION = 22012000005;
    private const string DOWNLOAD_URL_1  = 'https://raider.io/segments/42/1.txt';
    private const string DOWNLOAD_URL_2  = 'https://raider.io/segments/42/2.txt';

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenSegments_downloadsAndExtractsEachPart(): void
    {
        // Arrange
        $runContext       = new CombatLogRunContext(keyLevel: 10, affixIds: [9, 10]);
        $segmentsResponse = new CombatLogSegmentsResponse(
            sourceUserId: 1,
            segments:     [
                new CombatLogSegment(id: 2, type: 'combat_log', downloadUrl: self::DOWNLOAD_URL_2),
                new CombatLogSegment(id: 1, type: 'combat_log', downloadUrl: self::DOWNLOAD_URL_1),
            ],
        );

        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->expects($this->once())
            ->method('getCombatLogSegmentsForRun')
            ->with($this->isInstanceOf(Season::class), self::RUN_ID)
            ->willReturn($segmentsResponse);
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        // Each part is extracted independently (no combining), with the run context forwarded.
        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->expects($this->exactly(2))
            ->method('extractData')
            ->with(new IsType('string'), null, null, $this->identicalTo($runContext));
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        $log = $this->createMockPublic(ProcessCombatLogSegmentsLoggingInterface::class);
        $log->expects($this->once())->method('handleStart');
        $log->expects($this->never())->method('handleSegmentsNotAvailable');
        $log->expects($this->exactly(2))->method('handleDownloadingSegment');
        $log->expects($this->never())->method('handleSegmentDownloadFailed');
        $log->expects($this->never())->method('handleParseError');
        $log->expects($this->once())->method('handleEnd')->with(self::RUN_ID, true);
        app()->instance(ProcessCombatLogSegmentsLoggingInterface::class, $log);

        $job = $this->getMockBuilder(ProcessCombatLogSegments::class)
            ->setConstructorArgs([new Season(), self::RUN_ID, self::COMBAT_LOG_VERSION, $runContext])
            ->onlyMethods(['curlSaveToFile'])
            ->getMock();

        $job->expects($this->exactly(2))
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
    public function handle_givenZipAndPlainTextSegments_savesZipAsArchiveAndRestAsText(): void
    {
        // Arrange — only a `.zip` URL needs extracting; the `.txt.gz`-named Raider.IO segments arrive already
        // decompressed (via content encoding) and are saved as plain `.txt` so they are parsed as-is.
        $segmentsResponse = new CombatLogSegmentsResponse(
            sourceUserId: 1,
            segments:     [
                new CombatLogSegment(id: 1, type: 'combat_log', downloadUrl: 'https://s3.example.com/WoWCombatLog-1.zip?X-Amz-Signature=abc'),
                new CombatLogSegment(id: 2, type: 'combat_log', downloadUrl: 'https://s3.example.com/02_boss.txt.gz?X-Amz-Signature=def'),
            ],
        );

        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->method('getCombatLogSegmentsForRun')->willReturn($segmentsResponse);
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        $extractedPaths    = [];
        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->method('extractData')
            ->willReturnCallback(function (string $filePath) use (&$extractedPaths): null {
                $extractedPaths[] = $filePath;

                return null;
            });
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        $log = $this->createMockPublic(ProcessCombatLogSegmentsLoggingInterface::class);
        app()->instance(ProcessCombatLogSegmentsLoggingInterface::class, $log);

        $job = $this->getMockBuilder(ProcessCombatLogSegments::class)
            ->setConstructorArgs([new Season(), self::RUN_ID, self::COMBAT_LOG_VERSION])
            ->onlyMethods(['curlSaveToFile'])
            ->getMock();
        $job->method('curlSaveToFile')->willReturn(true);

        // Act
        app()->call([$job, 'handle']);

        // Assert
        $this->assertCount(2, $extractedPaths);
        $this->assertStringEndsWith('.zip', $extractedPaths[0]);
        $this->assertStringEndsWith('.txt', $extractedPaths[1]);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenParseException_recordsParseFailureWithLineInfo(): void
    {
        // Arrange
        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->method('getCombatLogSegmentsForRun')
            ->willReturn(new CombatLogSegmentsResponse(
                sourceUserId: 1,
                segments:     [new CombatLogSegment(id: 1, type: 'combat_log', downloadUrl: self::DOWNLOAD_URL_1)],
            ));
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        $parseException = new CombatLogParseException(
            lineNumber: 257080,
            rawLine:    'SPELL_DAMAGE,Player-1084-0B4087DE,"Pa',
            message:    'Unbalanced quotes in string SPELL_DAMAGE,Player-1084-0B4087DE,"Pa',
            previous:   new InvalidArgumentException('Unbalanced quotes in string SPELL_DAMAGE,Player-1084-0B4087DE,"Pa'),
        );

        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->method('extractData')->willThrowException($parseException);
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        // The parse error is swallowed and persisted (not re-thrown), so the run is not retried.
        $parseFailureRepository = $this->createMockPublic(CombatLogParseFailureRepositoryInterface::class);
        $parseFailureRepository->expects($this->once())
            ->method('recordFailure')
            ->with(
                self::RUN_ID,
                $this->isNull(),
                self::COMBAT_LOG_VERSION,
                257080,
                'SPELL_DAMAGE,Player-1084-0B4087DE,"Pa',
                'Unbalanced quotes in string SPELL_DAMAGE,Player-1084-0B4087DE,"Pa',
                InvalidArgumentException::class,
            );
        app()->instance(CombatLogParseFailureRepositoryInterface::class, $parseFailureRepository);

        $log = $this->createMockPublic(ProcessCombatLogSegmentsLoggingInterface::class);
        $log->expects($this->once())->method('handleParseError');
        $log->expects($this->once())->method('handleEnd')->with(self::RUN_ID, false);
        app()->instance(ProcessCombatLogSegmentsLoggingInterface::class, $log);

        $job = $this->getMockBuilder(ProcessCombatLogSegments::class)
            ->setConstructorArgs([new Season(), self::RUN_ID, self::COMBAT_LOG_VERSION])
            ->onlyMethods(['curlSaveToFile'])
            ->getMock();
        $job->method('curlSaveToFile')->willReturn(true);

        // Act
        app()->call([$job, 'handle']);

        // Assert — handled by mock expectations above
    }

    #[Test]
    public function uniqueId_givenRun_returnsRunIdAndJobIsUnique(): void
    {
        // Arrange
        $job = new ProcessCombatLogSegments(new Season(), self::RUN_ID, self::COMBAT_LOG_VERSION);

        // Act + Assert
        $this->assertInstanceOf(ShouldBeUnique::class, $job);
        $this->assertSame((string)self::RUN_ID, $job->uniqueId());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenNullSegmentsResponse_exitsEarlyWithoutExtracting(): void
    {
        // Arrange
        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->expects($this->once())
            ->method('getCombatLogSegmentsForRun')
            ->willReturn(null);
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->expects($this->never())->method('extractData');
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        $log = $this->createMockPublic(ProcessCombatLogSegmentsLoggingInterface::class);
        $log->expects($this->once())->method('handleSegmentsNotAvailable');
        $log->expects($this->once())->method('handleEnd')->with(self::RUN_ID, false);
        app()->instance(ProcessCombatLogSegmentsLoggingInterface::class, $log);

        // Act
        app()->call([new ProcessCombatLogSegments(new Season(), self::RUN_ID, self::COMBAT_LOG_VERSION), 'handle']);

        // Assert — handled by mock expectations above
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenEmptySegments_exitsEarlyWithoutExtracting(): void
    {
        // Arrange
        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->expects($this->once())
            ->method('getCombatLogSegmentsForRun')
            ->willReturn(new CombatLogSegmentsResponse(sourceUserId: 1, segments: []));
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->expects($this->never())->method('extractData');
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        $log = $this->createMockPublic(ProcessCombatLogSegmentsLoggingInterface::class);
        $log->expects($this->once())->method('handleSegmentsNotAvailable');
        $log->expects($this->once())->method('handleEnd')->with(self::RUN_ID, false);
        app()->instance(ProcessCombatLogSegmentsLoggingInterface::class, $log);

        // Act
        app()->call([new ProcessCombatLogSegments(new Season(), self::RUN_ID, self::COMBAT_LOG_VERSION), 'handle']);

        // Assert — handled by mock expectations above
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenSegmentDownloadFails_throwsRuntimeExceptionForRetry(): void
    {
        // Arrange
        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->expects($this->once())
            ->method('getCombatLogSegmentsForRun')
            ->willReturn(new CombatLogSegmentsResponse(
                sourceUserId: 1,
                segments:     [new CombatLogSegment(id: 1, type: 'combat_log', downloadUrl: self::DOWNLOAD_URL_1)],
            ));
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->expects($this->never())->method('extractData');
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        $log = $this->createMockPublic(ProcessCombatLogSegmentsLoggingInterface::class);
        $log->expects($this->once())->method('handleSegmentDownloadFailed');
        $log->expects($this->once())->method('handleEnd')->with(self::RUN_ID, false);
        app()->instance(ProcessCombatLogSegmentsLoggingInterface::class, $log);

        $job = $this->getMockBuilder(ProcessCombatLogSegments::class)
            ->setConstructorArgs([new Season(), self::RUN_ID, self::COMBAT_LOG_VERSION])
            ->onlyMethods(['curlSaveToFile'])
            ->getMock();

        $job->expects($this->once())
            ->method('curlSaveToFile')
            ->willReturn(false);

        // Assert + Act
        $this->expectException(RuntimeException::class);
        app()->call([$job, 'handle']);
    }
}
