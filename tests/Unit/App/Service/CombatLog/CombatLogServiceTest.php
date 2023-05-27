<?php

namespace Tests\Unit\App\Service\CombatLog;

use PHPUnit\Framework\Assert;
use Tests\PublicTestCase;
use Tests\Unit\Fixtures\LoggingFixtures;
use Tests\Unit\Fixtures\ServiceFixtures;

class CombatLogServiceTest extends PublicTestCase
{

    /**
     * @test
     *
     * @param string $combatLogPath
     * @return void
     *
     * @group CombatLogService
     * @dataProvider parseEvent_ShouldParseTimestamp_GivenRawLogLine_DataProvider
     */
    public function parseEvent_ShouldParseTimestamp_GivenRawLogLine(string $combatLogPath)
    {
        // Arrange
        ini_set('memory_limit', '1G');
        $log              = LoggingFixtures::createCombatLogServiceLogging($this);
        $combatLogService = ServiceFixtures::getCombatLogServiceMock($this, $log, ['parseCombatLogToEvents']);

        // Act
        $events = $combatLogService->parseCombatLogToEvents(
            $combatLogPath
        );

        // Assert
        Assert::assertNotCount(0, $events);

        // Force garbage collection
        unset($events);
        gc_collect_cycles();
    }

    public function parseEvent_ShouldParseTimestamp_GivenRawLogLine_DataProvider(): array
    {
        return [
//            [
//                __DIR__ . '/Fixtures/2_underrot/combat.log',
//            ],
//            [
//                __DIR__ . '/Fixtures/4_neltharus/combat.log',
//            ],
//            [
//                __DIR__ . '/Fixtures/5_freehold/combat.log',
//            ],
//            [
//                __DIR__ . '/Fixtures/18_neltharus/combat.log',
//            ],
        ];
    }
}
