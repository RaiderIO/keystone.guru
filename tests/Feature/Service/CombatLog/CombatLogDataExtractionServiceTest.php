<?php

namespace Tests\Feature\Service\CombatLog;

use App\Models\CombatLog\CombatLogAnalyze;
use App\Models\CombatLog\CombatLogAnalyzeStatus;
use App\Models\CombatLog\ParsedCombatLog;
use App\Repositories\Interfaces\CombatLog\ParsedCombatLogRepositoryInterface;
use App\Service\CombatLog\CombatLogDataExtractionService;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\DataExtractors\DataExtractorFactoryInterface;
use App\Service\CombatLog\Logging\CombatLogDataExtractionServiceLoggingInterface;
use App\Service\Season\SeasonServiceInterface;
use ArrayObject;
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
