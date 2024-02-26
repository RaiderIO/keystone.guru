<?php

namespace Tests\Unit\App\Service\CombatLog;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Assert;
use Tests\TestCases\PublicTestCase;
use Tests\Unit\Fixtures\LoggingFixtures;
use Tests\Unit\Fixtures\ServiceFixtures;

class CombatLogServiceTest extends PublicTestCase
{

    /**
     *
     * @return void
     */
    #[Test]
    #[Group('CombatLogService')]
    #[DataProvider('parseCombatLogToEvents_GivenCombatLog_ShouldParseEventsWithoutErrors_DataProvider')]
    public function parseCombatLogToEvents_GivenCombatLog_ShouldParseEventsWithoutErrors(string $combatLogPath)
    {
        // Arrange
        ini_set('memory_limit', '1G');
        $log              = LoggingFixtures::createCombatLogServiceLogging($this);
        $combatLogService = ServiceFixtures::getCombatLogServiceMock($this, $log);

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

    public function parseCombatLogToEvents_GivenCombatLog_ShouldParseEventsWithoutErrors_DataProvider(): array
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
