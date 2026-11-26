<?php

namespace Tests\Feature\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\SpecialEvents\EncounterStart\EncounterStartInterface;
use App\Models\CombatLog\CombatLogAnalyze;
use App\Models\CombatLog\CombatLogAnalyzeStatus;
use App\Models\CombatLog\ParsedCombatLog;
use App\Models\Dungeon;
use App\Repositories\Interfaces\CombatLog\ParsedCombatLogRepositoryInterface;
use App\Service\CombatLog\CombatLogDataExtractionService;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\DataExtractors\DataExtractorFactoryInterface;
use App\Service\CombatLog\DataExtractors\DataExtractorInterface;
use App\Service\CombatLog\DataExtractors\FixedDataExtractorFactory;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use App\Service\CombatLog\Logging\CombatLogDataExtractionServiceLoggingInterface;
use App\Service\Season\SeasonServiceInterface;
use ArrayObject;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use RuntimeException;
use Tests\Fixtures\Traits\CreatesParseCountingCombatLogService;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('CombatLogDataExtractionService')]
final class CombatLogDataExtractionServiceTest extends PublicTestCase
{
    use CreatesParseCountingCombatLogService;

    /**
     * @throws Exception
     */
    #[Test]
    public function extractDataAsync_givenVerifyFailure_storesErrorStatusAndMessage(): void
    {
        // Arrange
        $combatLogAnalyze = null;

        try {
            $combatLogAnalyze = CombatLogAnalyze::create([
                'combat_log_path'   => sprintf('/tmp/verify-failure-%d.txt', random_int(1, PHP_INT_MAX)),
                'percent_completed' => 0,
                'status'            => CombatLogAnalyzeStatus::Queued,
            ]);

            $combatLogService = $this->createMockPublic(CombatLogServiceInterface::class);
            $combatLogService->expects($this->once())
                ->method('countCombatLogLines')
                ->willThrowException(new InvalidArgumentException('File is not a valid .zip file'));
            $combatLogService->expects($this->never())
                ->method('parseCombatLog');

            $service = $this->createService($combatLogService);

            // Act
            $result = $service->extractDataAsync($combatLogAnalyze->combat_log_path, $combatLogAnalyze);

            // Assert
            $this->assertNull($result);
            $combatLogAnalyze->refresh();
            $this->assertSame(CombatLogAnalyzeStatus::Error, $combatLogAnalyze->status);
            $this->assertSame('Unable to verify combat log: File is not a valid .zip file', $combatLogAnalyze->error);
        } finally {
            $combatLogAnalyze?->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function extractDataAsync_givenProcessingFailure_storesErrorStatusAndMessage(): void
    {
        // Arrange
        $combatLogAnalyze = null;

        try {
            $combatLogAnalyze = CombatLogAnalyze::create([
                'combat_log_path'   => sprintf('/tmp/processing-failure-%d.txt', random_int(1, PHP_INT_MAX)),
                'percent_completed' => 0,
                'status'            => CombatLogAnalyzeStatus::Queued,
            ]);

            $combatLogService = $this->createMockPublic(CombatLogServiceInterface::class);
            $combatLogService->method('countCombatLogLines')->willReturn(42);
            $combatLogService->expects($this->once())
                ->method('parseCombatLog')
                ->willThrowException(new RuntimeException('Malformed GUID'));

            $service = $this->createService($combatLogService);

            // Act
            $result = $service->extractDataAsync($combatLogAnalyze->combat_log_path, $combatLogAnalyze);

            // Assert
            $this->assertNull($result);
            $combatLogAnalyze->refresh();
            $this->assertSame(CombatLogAnalyzeStatus::Error, $combatLogAnalyze->status);
            $this->assertSame('Unable to process combat log: Malformed GUID', $combatLogAnalyze->error);
        } finally {
            $combatLogAnalyze?->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function extractDataAsync_givenValidCombatLog_parsesTheCombatLogOnce(): void
    {
        // Arrange
        $combatLogPath    = tempnam(sys_get_temp_dir(), 'combatlog_test_');
        $combatLogAnalyze = null;

        try {
            file_put_contents($combatLogPath, implode(PHP_EOL, [
                '5/13 16:22:53.381  COMBAT_LOG_VERSION,20,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,10.1.0,PROJECT_ID,1',
                '5/13 16:22:53.381  ZONE_CHANGE,657,"The Vortex Pinnacle",23',
                '5/13 16:22:53.381  MAP_CHANGE,325,"The Vortex Pinnacle",-111.332031,-1457.150024,1107.791016,-910.934998',
            ]) . PHP_EOL);

            $combatLogAnalyze = CombatLogAnalyze::create([
                'combat_log_path'   => $combatLogPath,
                'percent_completed' => 0,
                'status'            => CombatLogAnalyzeStatus::Queued,
            ]);

            $parsedFilePaths = new ArrayObject();
            $service         = $this->createService($this->createParseCountingCombatLogService($parsedFilePaths));

            // Act
            $result = $service->extractDataAsync($combatLogPath, $combatLogAnalyze);

            // Assert
            $this->assertNotNull($result);
            $this->assertSame([$combatLogPath], $parsedFilePaths->getArrayCopy());
            $combatLogAnalyze->refresh();
            $this->assertSame(CombatLogAnalyzeStatus::Completed, $combatLogAnalyze->status);
            $this->assertSame(100, $combatLogAnalyze->percent_completed);
        } finally {
            $combatLogAnalyze?->delete();
            ParsedCombatLog::query()->where('combat_log_path', $combatLogPath)->delete();
            if (file_exists($combatLogPath)) {
                unlink($combatLogPath);
            }
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function extractData_givenSegmentsJoinedIntoOneFile_keepsTheKeyLevelPastTheNextSegmentHeader(): void
    {
        // Arrange
        $combatLogPath = tempnam(sys_get_temp_dir(), 'combatlog_test_');

        try {
            $dungeon = Dungeon::query()->where('challenge_mode_id', '>', 0)->firstOrFail();
            $header  = static fn(int $segmentId, string $segmentType): string => sprintf(
                '11/25/2026 09:54:06.4591  RIO_LOG_VERSION,1,SEGMENT_TYPE,mplus_%1$s,APP_VERSION,5.0.9,PROCESSOR_VERSION,1,PLATFORM,win32,INSTANCE_ID,%2$d,DUNGEON_ID,%3$d,SEGMENT_ID,%4$d,CORRELATION_ID,abc,CHALLENGE_MODE_STARTED_AT,1795596846353,TYPE,%1$s,CLIENT_SESSION_ID,b328ed5f,COMBAT_LOG_VERSION,22,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,12.1.5,PROJECT_ID,1',
                $segmentType,
                $dungeon->map_id,
                $dungeon->challenge_mode_id,
                $segmentId,
            );
            file_put_contents($combatLogPath, implode(PHP_EOL, [
                $header(1, 'trash'),
                sprintf('11/25/2026 09:54:06.4591  CHALLENGE_MODE_START,"Dungeon",%d,%d,12,[10,9,147]', $dungeon->map_id, $dungeon->challenge_mode_id),
                $header(2, 'boss'),
                sprintf('11/25/2026 09:59:51.1601  ENCOUNTER_START,3285,"Boss",8,5,%d', $dungeon->map_id),
            ]) . PHP_EOL);

            $keyLevels = new ArrayObject();
            $extractor = new class($keyLevels) implements DataExtractorInterface {
                /**
                 * @param ArrayObject<int, int|null> $keyLevels
                 */
                public function __construct(private readonly ArrayObject $keyLevels)
                {
                }

                public function beforeExtract(ExtractedDataResult $result, string $combatLogFilePath): void
                {
                }

                public function extractData(ExtractedDataResult $result, DataExtractionCurrentDungeon $currentDungeon, BaseEvent $parsedEvent): void
                {
                    if ($parsedEvent instanceof EncounterStartInterface) {
                        $this->keyLevels->append($currentDungeon->keyLevel);
                    }
                }

                public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
                {
                }
            };

            /** @var Collection<int, DataExtractorInterface> $extractors */
            $extractors = collect([$extractor]);
            $service    = new CombatLogDataExtractionService(
                app(CombatLogServiceInterface::class),
                app(SeasonServiceInterface::class),
                app(ParsedCombatLogRepositoryInterface::class),
                $this->createMockPublic(CombatLogDataExtractionServiceLoggingInterface::class),
                new FixedDataExtractorFactory($extractors),
            );

            // Act
            $service->extractData($combatLogPath, true);

            // Assert
            $this->assertSame([12], $keyLevels->getArrayCopy());
        } finally {
            if (file_exists($combatLogPath)) {
                unlink($combatLogPath);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function createService(CombatLogServiceInterface $combatLogService): CombatLogDataExtractionService
    {
        $dataExtractorFactory = $this->createMockPublic(DataExtractorFactoryInterface::class);
        $dataExtractorFactory->method('createExtractors')->willReturn(collect());

        return new CombatLogDataExtractionService(
            $combatLogService,
            app(SeasonServiceInterface::class),
            app(ParsedCombatLogRepositoryInterface::class),
            $this->createMockPublic(CombatLogDataExtractionServiceLoggingInterface::class),
            $dataExtractorFactory,
        );
    }
}
