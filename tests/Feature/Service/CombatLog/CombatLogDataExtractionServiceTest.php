<?php

namespace Tests\Feature\Service\CombatLog;

use App\Models\CombatLog\CombatLogAnalyze;
use App\Models\CombatLog\CombatLogAnalyzeStatus;
use App\Repositories\Interfaces\CombatLog\ParsedCombatLogRepositoryInterface;
use App\Service\CombatLog\CombatLogDataExtractionService;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\DataExtractors\DataExtractorFactoryInterface;
use App\Service\CombatLog\Logging\CombatLogDataExtractionServiceLoggingInterface;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('CombatLogDataExtractionService')]
final class CombatLogDataExtractionServiceTest extends PublicTestCase
{
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
                ->method('parseCombatLog')
                ->willThrowException(new RuntimeException('Unexpected token on line 42'));

            $service = $this->createService($combatLogService);

            // Act
            $result = $service->extractDataAsync($combatLogAnalyze->combat_log_path, $combatLogAnalyze);

            // Assert
            $this->assertNull($result);
            $combatLogAnalyze->refresh();
            $this->assertSame(CombatLogAnalyzeStatus::Error, $combatLogAnalyze->status);
            $this->assertSame('Unable to verify combat log: Unexpected token on line 42', $combatLogAnalyze->error);
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

            $parseCalls       = 0;
            $combatLogService = $this->createMockPublic(CombatLogServiceInterface::class);
            $combatLogService->expects($this->exactly(2))
                ->method('parseCombatLog')
                ->willReturnCallback(static function () use (&$parseCalls): void {
                    // The first call is the verify pass, the second the processing pass
                    if (++$parseCalls === 2) {
                        throw new RuntimeException('Malformed GUID');
                    }
                });

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
