<?php

namespace Tests\Unit\Fixtures;

use App\Service\CombatLog\Logging\CombatLogDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogServiceLoggingInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\PublicTestCase;

class LoggingFixtures
{
    /**
     * @param PublicTestCase $testCase
     * @return MockObject|CombatLogServiceLoggingInterface
     */
    public static function createCombatLogServiceLogging(
        PublicTestCase $testCase
    ): MockObject
    {
        return $testCase->createMock(CombatLogServiceLoggingInterface::class);
    }

    /**
     * @param PublicTestCase $testCase
     * @return MockObject|CombatLogDungeonRouteServiceLoggingInterface
     */
    public static function createCombatLogDungeonRouteServiceLogging(
        PublicTestCase $testCase
    ): MockObject
    {
        return $testCase->createMock(CombatLogDungeonRouteServiceLoggingInterface::class);
    }
}
