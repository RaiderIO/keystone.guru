<?php

namespace Tests\Feature\Environment;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards that phpunit.xml's <php> block actually reaches the application.
 *
 * Those entries only apply when the variable is not already set in the process environment: PHPUnit leaves an
 * existing one alone, and Laravel's Env reads $_SERVER - where a container's environment lands - before $_ENV,
 * where PHPUnit writes. Anything injecting these into the container therefore wins silently.
 *
 * When that happened the suite ran against the live dev schema on live redis, every page-rendering test 500'd
 * because no view composers registered, and every API test 401'd because ApiAuthentication stopped skipping
 * itself - while CI stayed green, having no such injection. Nothing else in the suite fails when this block
 * stops taking effect, so this test is the only thing that turns it from silent into loud.
 */
#[Group('Environment')]
final class TestEnvironmentTest extends TestCase
{
    #[Test]
    public function environment_givenTestRun_isTesting(): void
    {
        // Arrange

        // Act
        $environment = app()->environment();

        // Assert
        $this->assertSame('testing', $environment);
    }

    #[Test]
    public function runningUnitTests_givenTestRun_returnsTrue(): void
    {
        // Arrange - KeystoneGuruServiceProvider::boot() returns early without this, registering no view
        // composers, which makes every page-rendering test fail on an undefined view variable

        // Act
        $runningUnitTests = app()->runningUnitTests();

        // Assert
        $this->assertTrue($runningUnitTests);
    }

    #[Test]
    public function databaseConnection_givenTestRun_isTheDedicatedPhpunitConnection(): void
    {
        // Arrange - #4346 exists so tests cannot write-lock or pollute the shared dev schema

        // Act
        $connection = config('database.default');

        // Assert
        $this->assertSame('phpunit', $connection);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function isolatedDriverProvider(): array
    {
        return [
            'cache'   => ['cache.default', 'array'],
            'session' => ['session.driver', 'array'],
            'queue'   => ['queue.default', 'sync'],
            // #4483 - a developer's local .env can carry a real Mailgun secret; the suite must never send real mail
            'mail' => ['mail.driver', 'log'],
        ];
    }

    #[Test]
    #[DataProvider('isolatedDriverProvider')]
    public function driver_givenTestRun_isIsolatedFromTheDevStack(string $configKey, string $expectedDriver): void
    {
        // Arrange - a redis driver, or a real third-party service, here is reachable from the dev stack

        // Act
        $driver = config($configKey);

        // Assert
        $this->assertSame($expectedDriver, $driver, sprintf('%s must not reach the dev stack during tests', $configKey));
    }
}
