<?php

namespace Tests\Unit\App\Providers;

use App\Exceptions\Logging\HandlerLoggingInterface;
use App\Providers\LoggingServiceProvider;
use App\Service\CombatLog\Filters\Logging\BaseCombatFilterLoggingInterface;
use App\Service\WowTools\Logging\WowToolsServiceLoggingInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Providers')]
#[Group('LoggingServiceProvider')]
class LoggingServiceProviderTest extends PublicTestCase
{
    #[Test]
    public function getLoggingInterfaces_GivenConventionCompliantCodebase_ShouldDiscoverInterfacesAtAllDepths(): void
    {
        // Act
        $loggingInterfaces = LoggingServiceProvider::getLoggingInterfaces();

        // Assert
        // One interface per directory depth that occurs in the codebase - a glob pattern regression would drop one of these
        self::assertContains(HandlerLoggingInterface::class, $loggingInterfaces);
        self::assertContains(WowToolsServiceLoggingInterface::class, $loggingInterfaces);
        self::assertContains(BaseCombatFilterLoggingInterface::class, $loggingInterfaces);
    }

    #[Test]
    public function register_GivenDiscoveredLoggingInterfaces_ShouldResolveConventionBoundImplementations(): void
    {
        // Arrange
        $loggingInterfaces = LoggingServiceProvider::getLoggingInterfaces();

        self::assertNotEmpty($loggingInterfaces);

        foreach ($loggingInterfaces as $loggingInterface) {
            // Act
            $instance = app($loggingInterface);

            // Assert
            self::assertInstanceOf($loggingInterface, $instance);
        }
    }
}
