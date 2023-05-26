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
     * @return void
     * @group CombatLogService
     */
    public function parseEvent_ShouldParseTimestamp_GivenRawLogLine()
    {
        // Arrange
        $log              = LoggingFixtures::createCombatLogServiceLogging($this);
        $combatLogService = ServiceFixtures::getCombatLogServiceMock($this, $log, ['parseCombatLogToEvents']);

        // Act
        $events = $combatLogService->parseCombatLogToEvents(
            __DIR__ . '/Fixtures/2_underrot/combat.log'
        );

        // Assert
        Assert::assertNotCount(0, $events);
    }

}
