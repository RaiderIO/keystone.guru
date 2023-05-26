<?php

namespace Tests\Unit\Fixtures;

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
}
