<?php

namespace Tests\Fixtures;

use App\Service\AffixGroup\Logging\AffixGroupEaseTierServiceLoggingInterface;
use App\Service\Cache\Logging\CacheServiceLoggingInterface;
use App\Service\Cloudflare\Logging\CloudflareServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogServiceLoggingInterface;
use App\Service\CombatLogEvent\Logging\CombatLogEventServiceLoggingInterface;
use App\Service\Spell\Logging\SpellServiceLoggingInterface;
use Illuminate\Log\LogManager;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

class LoggingFixtures
{
    /**
     * @throws Exception
     */
    public static function createLogManager(
        PublicTestCase $testCase,
    ): MockObject|LogManager {
        $logManager = $testCase->createMockPublic(LogManager::class);
        $logManager->method('channel')->willReturnSelf();

        return $logManager;
    }

    /**
     * @throws Exception
     */
    public static function createAffixGroupEaseTierServiceLogging(
        PublicTestCase $testCase,
    ): MockObject|AffixGroupEaseTierServiceLoggingInterface {
        return $testCase->createMockPublic(AffixGroupEaseTierServiceLoggingInterface::class);
    }

    /**
     * @throws Exception
     */
    public static function createCombatLogServiceLogging(
        PublicTestCase $testCase,
    ): MockObject|CombatLogServiceLoggingInterface {
        return $testCase->createMockPublic(CombatLogServiceLoggingInterface::class);
    }

    /**
     * @throws Exception
     */
    public static function createCombatLogDungeonRouteServiceLogging(
        PublicTestCase $testCase,
    ): MockObject|CombatLogDungeonRouteServiceLoggingInterface {
        return $testCase->createMockPublic(CombatLogDungeonRouteServiceLoggingInterface::class);
    }

    /**
     * @throws Exception
     */
    public static function createSpellServiceLogging(
        PublicTestCase $testCase,
    ): MockObject|SpellServiceLoggingInterface {
        return $testCase->createMockPublic(SpellServiceLoggingInterface::class);
    }

    /**
     * @throws Exception
     */
    public static function createCombatLogEventServiceLogging(
        PublicTestCase $testCase,
    ): MockObject|CombatLogEventServiceLoggingInterface {
        return $testCase->createMockPublic(CombatLogEventServiceLoggingInterface::class);
    }

    /**
     * @throws Exception
     */
    public static function createCloudflareServiceLogging(
        PublicTestCase $testCase,
    ): MockObject|CloudflareServiceLoggingInterface {
        return $testCase->createMockPublic(CloudflareServiceLoggingInterface::class);
    }

    /**
     * @throws Exception
     */
    public static function createCacheServiceLogging(
        PublicTestCase $testCase,
    ): MockObject|CacheServiceLoggingInterface {
        return $testCase->createMockPublic(CacheServiceLoggingInterface::class);
    }
}
