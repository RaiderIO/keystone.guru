<?php

namespace Tests\Feature\Jobs\CombatLog;

use App\Jobs\CombatLog\ProcessCombatLogSegments;
use App\Jobs\Logging\ProcessCombatLogSegmentsLoggingInterface;
use App\Models\CharacterClassSpecialization;
use App\Models\Dungeon;
use App\Models\Season;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use App\Service\CombatLog\CombatLogParsingCriteriaServiceInterface;
use App\Service\CombatLog\CombatLogPollingHealthServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use App\Service\CombatLog\Dtos\CombatLogRunContext;
use App\Service\CombatLog\Dtos\KeyLevelBand;
use App\Service\CombatLog\Enums\CombatLogPollingFailureReason;
use App\Service\CombatLog\Exceptions\CombatLogParseException;
use App\Service\RaiderIO\Dtos\CombatLogSegment;
use App\Service\RaiderIO\Dtos\CombatLogSegmentsResponse;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\Exception;
use RedisException;
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
    private const string CRITERIA_DATE   = '2026-08-20';

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
        $job->method('curlSaveToFile')->willReturnCallback($this->writeSegmentContents(...));

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
    public function handle_givenParseException_reportsParseErrorWithLineInfo(): void
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

        // The parse error is swallowed and only reported (not re-thrown), so the run is not retried.
        $log = $this->createMockPublic(ProcessCombatLogSegmentsLoggingInterface::class);
        // Asserted in full: this log line is now the only record of the failure, and it must name the cause
        // (InvalidArgumentException) rather than the CombatLogParseException wrapper - the error tracker
        // fingerprints and tags on exactly these values.
        $log->expects($this->once())
            ->method('handleParseError')
            ->with(
                self::RUN_ID,
                $this->isNull(),
                self::COMBAT_LOG_VERSION,
                257080,
                InvalidArgumentException::class,
                'Unbalanced quotes in string SPELL_DAMAGE,Player-1084-0B4087DE,"Pa',
                'SPELL_DAMAGE,Player-1084-0B4087DE,"Pa',
            );
        $log->expects($this->once())->method('handleEnd')->with(self::RUN_ID, false);
        app()->instance(ProcessCombatLogSegmentsLoggingInterface::class, $log);

        $job = $this->getMockBuilder(ProcessCombatLogSegments::class)
            ->setConstructorArgs([new Season(), self::RUN_ID, self::COMBAT_LOG_VERSION])
            ->onlyMethods(['curlSaveToFile'])
            ->getMock();
        $job->method('curlSaveToFile')->willReturnCallback($this->writeSegmentContents(...));

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

    /**
     * @throws Exception
     */
    #[Test]
    #[DataProvider('implausibleSegmentBodyProvider')]
    public function handle_givenSegmentIsNotACombatLog_throwsRuntimeExceptionInsteadOfReportingParseError(
        string $body,
    ): void {
        // Arrange — a bad download that still arrives with a 2xx status, so the download itself "succeeds".
        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->method('getCombatLogSegmentsForRun')
            ->willReturn(new CombatLogSegmentsResponse(
                sourceUserId: 1,
                segments:     [new CombatLogSegment(id: 1, type: 'combat_log', downloadUrl: self::DOWNLOAD_URL_1)],
            ));
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->expects($this->never())->method('extractData');
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        // The download is retryable, so it must not be reported as a parse failure blaming the parser.
        $log = $this->createMockPublic(ProcessCombatLogSegmentsLoggingInterface::class);
        $log->expects($this->once())->method('handleSegmentIsNotACombatLog');
        $log->expects($this->never())->method('handleParseError');
        $log->expects($this->once())->method('handleEnd')->with(self::RUN_ID, false);
        app()->instance(ProcessCombatLogSegmentsLoggingInterface::class, $log);

        $job = $this->getMockBuilder(ProcessCombatLogSegments::class)
            ->setConstructorArgs([new Season(), self::RUN_ID, self::COMBAT_LOG_VERSION])
            ->onlyMethods(['curlSaveToFile'])
            ->getMock();

        $job->method('curlSaveToFile')
            ->willReturnCallback(function (string $url, string $tempPath) use ($body): bool {
                file_put_contents($tempPath, $body);

                return true;
            });

        // Assert + Act
        $this->expectException(RuntimeException::class);
        app()->call([$job, 'handle']);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenRedisExceptionDuringExtraction_throwsForRetryInsteadOfReportingParseError(): void
    {
        // Arrange — a dropped Redis/Valkey connection mid-parse (#3791) is a transient infra failure, not an
        // unparsable line: it must be retried by the queue rather than reported as a permanent parse failure.
        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->method('getCombatLogSegmentsForRun')
            ->willReturn(new CombatLogSegmentsResponse(
                sourceUserId: 1,
                segments:     [new CombatLogSegment(id: 1, type: 'combat_log', downloadUrl: self::DOWNLOAD_URL_1)],
            ));
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        $extractionService->method('extractData')
            ->willThrowException(new RedisException('read error on connection to tcp://ksg-valkey:6379'));
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        $log = $this->createMockPublic(ProcessCombatLogSegmentsLoggingInterface::class);
        $log->expects($this->never())->method('handleParseError');
        app()->instance(ProcessCombatLogSegmentsLoggingInterface::class, $log);

        $job = $this->getMockBuilder(ProcessCombatLogSegments::class)
            ->setConstructorArgs([new Season(), self::RUN_ID, self::COMBAT_LOG_VERSION])
            ->onlyMethods(['curlSaveToFile'])
            ->getMock();
        $job->method('curlSaveToFile')->willReturnCallback($this->writeSegmentContents(...));

        // Assert + Act
        $this->expectException(RedisException::class);
        app()->call([$job, 'handle']);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenEmptySegments_releasesCriteriaBudgetAndCountsFailure(): void
    {
        // Arrange - a run that yields nothing must not keep the budget it was dispatched on
        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->method('getCombatLogSegmentsForRun')
            ->willReturn(new CombatLogSegmentsResponse(sourceUserId: 1, segments: []));
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        $criteriaService = $this->createMockPublic(CombatLogParsingCriteriaServiceInterface::class);
        $criteriaService->expects($this->once())
            ->method('releaseParsed')
            ->with(self::COMBAT_LOG_VERSION, $this->criteria(), self::CRITERIA_DATE);
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        // An empty segments array has no upstream counter, so the job is what counts it
        $healthService = $this->createMockPublic(CombatLogPollingHealthServiceInterface::class);
        $healthService->expects($this->once())
            ->method('recordFailure')
            ->with(CombatLogPollingFailureReason::SegmentsUnavailable);
        $healthService->expects($this->never())->method('recordSucceeded');
        app()->instance(CombatLogPollingHealthServiceInterface::class, $healthService);

        // Act
        app()->call([$this->jobWithCriteria(), 'handle']);

        // Assert - handled by mock expectations above
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenNullSegmentsResponse_releasesCriteriaBudgetAndCountsFailure(): void
    {
        // Arrange - the job counts this, not RaiderIOApiService, which also serves non-polling callers
        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->method('getCombatLogSegmentsForRun')->willReturn(null);
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        $criteriaService = $this->createMockPublic(CombatLogParsingCriteriaServiceInterface::class);
        $criteriaService->expects($this->once())
            ->method('releaseParsed')
            ->with(self::COMBAT_LOG_VERSION, $this->criteria(), self::CRITERIA_DATE);
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $healthService = $this->createMockPublic(CombatLogPollingHealthServiceInterface::class);
        $healthService->expects($this->once())
            ->method('recordFailure')
            ->with(CombatLogPollingFailureReason::SegmentsUnavailable);
        app()->instance(CombatLogPollingHealthServiceInterface::class, $healthService);

        // Act
        app()->call([$this->jobWithCriteria(), 'handle']);

        // Assert - handled by mock expectations above
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenParseError_releasesCriteriaBudgetAndCountsFailure(): void
    {
        // Arrange
        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->method('getCombatLogSegmentsForRun')
            ->willReturn(new CombatLogSegmentsResponse(
                sourceUserId: 1,
                segments:     [new CombatLogSegment(id: 1, type: 'combat_log', downloadUrl: self::DOWNLOAD_URL_1)],
            ));
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        // The real exception the parser throws: it extends Exception, not RuntimeException, precisely
        // so it lands in the parse-error branch instead of being re-thrown for a retry
        $extractionService->method('extractData')
            ->willThrowException(new CombatLogParseException(
                42,
                'SPELL_DAMAGE,Player-1084-0B4087DE,"Pa',
                'Unable to parse line',
                new InvalidArgumentException('Unable to parse line'),
            ));
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        $criteriaService = $this->createMockPublic(CombatLogParsingCriteriaServiceInterface::class);
        $criteriaService->expects($this->once())
            ->method('releaseParsed')
            ->with(self::COMBAT_LOG_VERSION, $this->criteria(), self::CRITERIA_DATE);
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $healthService = $this->createMockPublic(CombatLogPollingHealthServiceInterface::class);
        $healthService->expects($this->once())
            ->method('recordFailure')
            ->with(CombatLogPollingFailureReason::ParseFailed);
        app()->instance(CombatLogPollingHealthServiceInterface::class, $healthService);

        $job = $this->getMockBuilder(ProcessCombatLogSegments::class)
            ->setConstructorArgs([new Season(), self::RUN_ID, self::COMBAT_LOG_VERSION, null, $this->criteria(), self::CRITERIA_DATE])
            ->onlyMethods(['curlSaveToFile'])
            ->getMock();
        $job->method('curlSaveToFile')->willReturnCallback($this->writeSegmentContents(...));

        // Act
        app()->call([$job, 'handle']);

        // Assert - handled by mock expectations above
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenSuccessfulExtraction_keepsCriteriaBudgetAndCountsSuccess(): void
    {
        // Arrange
        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->method('getCombatLogSegmentsForRun')
            ->willReturn(new CombatLogSegmentsResponse(
                sourceUserId: 1,
                segments:     [new CombatLogSegment(id: 1, type: 'combat_log', downloadUrl: self::DOWNLOAD_URL_1)],
            ));
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        $extractionService = $this->createMockPublic(CombatLogDataExtractionServiceInterface::class);
        app()->instance(CombatLogDataExtractionServiceInterface::class, $extractionService);

        $criteriaService = $this->createMockPublic(CombatLogParsingCriteriaServiceInterface::class);
        $criteriaService->expects($this->never())->method('releaseParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $healthService = $this->createMockPublic(CombatLogPollingHealthServiceInterface::class);
        $healthService->expects($this->once())->method('recordSucceeded');
        $healthService->expects($this->never())->method('recordFailure');
        app()->instance(CombatLogPollingHealthServiceInterface::class, $healthService);

        $job = $this->getMockBuilder(ProcessCombatLogSegments::class)
            ->setConstructorArgs([new Season(), self::RUN_ID, self::COMBAT_LOG_VERSION, null, $this->criteria(), self::CRITERIA_DATE])
            ->onlyMethods(['curlSaveToFile'])
            ->getMock();
        $job->method('curlSaveToFile')->willReturnCallback($this->writeSegmentContents(...));

        // Act
        app()->call([$job, 'handle']);

        // Assert - handled by mock expectations above
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function failed_givenRetriesExhausted_releasesCriteriaBudgetAndCountsFailure(): void
    {
        // Arrange - the download failures handle() re-throws for a retry end up here once they run out
        $criteriaService = $this->createMockPublic(CombatLogParsingCriteriaServiceInterface::class);
        $criteriaService->expects($this->once())
            ->method('releaseParsed')
            ->with(self::COMBAT_LOG_VERSION, $this->criteria(), self::CRITERIA_DATE);
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        $healthService = $this->createMockPublic(CombatLogPollingHealthServiceInterface::class);
        $healthService->expects($this->once())
            ->method('recordFailure')
            ->with(CombatLogPollingFailureReason::RunFailedAfterRetries);
        app()->instance(CombatLogPollingHealthServiceInterface::class, $healthService);

        // Act
        $this->jobWithCriteria()->failed(new RuntimeException('Failed to download segment 1 for run 42'));

        // Assert - handled by mock expectations above
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenNoCriteria_releasesNothing(): void
    {
        // Arrange - runs dispatched outside combatlog:pollruns carry no budget to give back
        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->method('getCombatLogSegmentsForRun')->willReturn(null);
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        $criteriaService = $this->createMockPublic(CombatLogParsingCriteriaServiceInterface::class);
        $criteriaService->expects($this->never())->method('releaseParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        // Act
        app()->call([new ProcessCombatLogSegments(new Season(), self::RUN_ID, self::COMBAT_LOG_VERSION), 'handle']);

        // Assert - handled by mock expectations above
    }

    /**
     * The criteria a polled run is dispatched with: the dungeon it was in, and the specs that were in it.
     *
     * @return CombatLogParsingCriterionCheck[]
     */
    private function criteria(): array
    {
        $band = new KeyLevelBand(12, 16);

        return [
            new CombatLogParsingCriterionCheck(Dungeon::class, 1, $band),
            new CombatLogParsingCriterionCheck(CharacterClassSpecialization::class, 2, $band),
        ];
    }

    private function jobWithCriteria(): ProcessCombatLogSegments
    {
        return new ProcessCombatLogSegments(
            new Season(),
            self::RUN_ID,
            self::COMBAT_LOG_VERSION,
            null,
            $this->criteria(),
            self::CRITERIA_DATE,
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function implausibleSegmentBodyProvider(): array
    {
        return [
            'xml error document' => ['<?xml version="1.0" encoding="UTF-8"?><Error><Code>AccessDenied</Code></Error>'],
            'html error page'    => ['<html><body>403 Forbidden</body></html>'],
            'empty body'         => [''],
        ];
    }

    /**
     * Stand-in for a successful download: writes plausible combat log content to the segment's temp path.
     */
    private function writeSegmentContents(string $url, string $tempPath): bool
    {
        file_put_contents($tempPath, sprintf('8/2/2026 20:15:01.123-4  ENCOUNTER_START,from %s', $url));

        return true;
    }
}
