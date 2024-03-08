<?php /** @noinspection PhpDocSignatureInspection */

namespace Tests\Unit\Fixtures;

use App\Service\AffixGroup\Logging\AffixGroupEaseTierServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogServiceLoggingInterface;
use Illuminate\Log\LogManager;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

class LoggingFixtures
{
    /**
     * @return MockObject|LogManager
     * @throws Exception
     */
    public static function createLogManager(
        PublicTestCase $testCase
    ): MockObject {
        $logManager = $testCase->createMock(LogManager::class);
        $logManager->method('channel')->willReturnSelf();

        return $logManager;
    }

    /**
     * @return MockObject|AffixGroupEaseTierServiceLoggingInterface
     * @throws Exception
     */
    public static function createAffixGroupEaseTierServiceLogging(
        PublicTestCase $testCase
    ): MockObject {
        return $testCase->createMock(AffixGroupEaseTierServiceLoggingInterface::class);
    }

    /**
     * @return MockObject|CombatLogServiceLoggingInterface
     * @throws Exception
     */
    public static function createCombatLogServiceLogging(
        PublicTestCase $testCase
    ): MockObject {
        return $testCase->createMock(CombatLogServiceLoggingInterface::class);
    }

    /**
     * @return MockObject|CombatLogDungeonRouteServiceLoggingInterface
     * @throws Exception
     */
    public static function createCombatLogDungeonRouteServiceLogging(
        PublicTestCase $testCase
    ): MockObject {
        return $testCase->createMock(CombatLogDungeonRouteServiceLoggingInterface::class);
    }
}
