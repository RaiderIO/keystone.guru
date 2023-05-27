<?php

namespace Tests\Unit\App\Service\CombatLog;

use PHPUnit\Framework\Assert;
use Tests\PublicTestCase;
use Tests\Unit\Fixtures\LoggingFixtures;
use Tests\Unit\Fixtures\ServiceFixtures;

class CombatLogDungeonRouteServiceTest extends PublicTestCase
{

    /**
     * @test
     *
     * @param string $combatLogPath
     * @return void
     *
     * @group CombatLogDungeonRouteService
     * @dataProvider parseEvent_ShouldParseTimestamp_GivenRawLogLine_DataProvider
     */
    public function parseEvent_ShouldParseTimestamp_GivenRawLogLine(string $combatLogPath)
    {
        // Arrange
        ini_set('memory_limit', '1G');
        $combatLogServiceLog = LoggingFixtures::createCombatLogServiceLogging($this);
        $combatLogService    = ServiceFixtures::getCombatLogServiceMock($this, $combatLogServiceLog);

        $combatLogDungeonRouteServiceLog = LoggingFixtures::createCombatLogDungeonRouteServiceLogging($this);
        $combatLogDungeonRouteService    = ServiceFixtures::getCombatLogDungeonRouteServiceMock(
            $this,
            $combatLogService,
            $combatLogDungeonRouteServiceLog
        );

        // Act
        $dungeonRoute = $combatLogDungeonRouteService->convertCombatLogToDungeonRoute(
            $combatLogPath
        );

        // Assert

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
