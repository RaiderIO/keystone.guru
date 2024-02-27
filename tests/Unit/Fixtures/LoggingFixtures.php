<?php

namespace Tests\Unit\Fixtures;

use App\Service\AffixGroup\Logging\AffixGroupEaseTierServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogServiceLoggingInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

class LoggingFixtures
{
    /**
     * @return MockObject|AffixGroupEaseTierServiceLoggingInterface
     */
    public static function createAffixGroupEaseTierServiceLogging(
        PublicTestCase $testCase
    ): MockObject {
        return $testCase->createMock(AffixGroupEaseTierServiceLoggingInterface::class);
    }

    /**
     * @return MockObject|CombatLogServiceLoggingInterface
     */
    public static function createCombatLogServiceLogging(
        PublicTestCase $testCase
    ): MockObject {
        return $testCase->createMock(CombatLogServiceLoggingInterface::class);
    }

    /**
     * @return MockObject|CombatLogDungeonRouteServiceLoggingInterface
     */
    public static function createCombatLogDungeonRouteServiceLogging(
        PublicTestCase $testCase
    ): MockObject {
        return $testCase->createMock(CombatLogDungeonRouteServiceLoggingInterface::class);
    }
}
