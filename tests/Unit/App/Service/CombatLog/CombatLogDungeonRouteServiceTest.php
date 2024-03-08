<?php

namespace Tests\Unit\App\Service\CombatLog;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;
use Tests\Unit\Fixtures\LoggingFixtures;
use Tests\Unit\Fixtures\ServiceFixtures;

final class CombatLogDungeonRouteServiceTest extends PublicTestCase
{
//    #[Test]
//    #[Group('CombatLogDungeonRouteService')]
//    #[DataProvider('parseEvent_ShouldParseTimestamp_GivenRawLogLine_DataProvider')]
//    public function parseEvent_ShouldParseTimestamp_GivenRawLogLine(string $combatLogPath): void
//    {
//        // Arrange
//        ini_set('memory_limit', '1G');
//        $combatLogServiceLog = LoggingFixtures::createCombatLogServiceLogging($this);
//        $combatLogService    = ServiceFixtures::getCombatLogServiceMock($this, $combatLogServiceLog);
//
//        $combatLogDungeonRouteServiceLog = LoggingFixtures::createCombatLogDungeonRouteServiceLogging($this);
//        $combatLogDungeonRouteService    = ServiceFixtures::getResultEventDungeonRouteServiceMock(
//            $this,
//            $combatLogService,
//            $combatLogDungeonRouteServiceLog
//        );
//
//        // Act
//        $dungeonRoute = $combatLogDungeonRouteService->convertCombatLogToDungeonRoutes(
//            $combatLogPath
//        );
//
//        // Assert
//
//    }
//
//    public static function parseEvent_ShouldParseTimestamp_GivenRawLogLine_DataProvider(): array
//    {
//        return [
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
//        ];
//    }
}
