<?php

namespace Tests\Unit\Tests\Traits;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('ParallelTesting')]
class UsesParallelTestTokenTest extends TestCase
{
    #[Test]
    #[DataProvider('schemaNameProvider')]
    public function resolveParallelSchemaName_givenBaseAndToken_returnsExpectedName(string $base, ?string $token, string $expected): void
    {
        // Act
        $result = self::resolveParallelSchemaName($base, $token);

        // Assert
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, ?string, string}>
     */
    public static function schemaNameProvider(): array
    {
        return [
            'no token leaves the name alone'    => ['keystone.guru.dev', null, 'keystone.guru.dev'],
            'empty token leaves the name alone' => ['keystone.guru.dev', '', 'keystone.guru.dev'],
            'token is suffixed'                 => ['keystone.guru.dev', '3', 'keystone.guru.dev_3'],
            'combatlog schema'                  => ['keystone.guru.combatlog', '1', 'keystone.guru.combatlog_1'],
        ];
    }

    #[Test]
    #[DataProvider('pathProvider')]
    public function resolveParallelPath_givenBaseAndToken_returnsExpectedPath(string $base, ?string $token, string $expected): void
    {
        // Act
        $result = self::resolveParallelPath($base, $token);

        // Assert
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, ?string, string}>
     */
    public static function pathProvider(): array
    {
        return [
            'no token leaves a file alone'         => ['/tmp/phpunit_services.php', null, '/tmp/phpunit_services.php'],
            'no token leaves a directory alone'    => ['/tmp/phpunit_cache', null, '/tmp/phpunit_cache'],
            'file gets the token before extension' => ['/tmp/phpunit_services.php', '2', '/tmp/phpunit_services_2.php'],
            'directory gets a token subdirectory'  => ['/tmp/phpunit_cache', '2', '/tmp/phpunit_cache/2'],
            'trailing slash is normalised'         => ['/tmp/phpunit_cache/', '4', '/tmp/phpunit_cache/4'],
        ];
    }

    #[Test]
    public function parallelTestToken_givenRunningUnderParatest_matchesTheDatabaseNameInUse(): void
    {
        // Arrange
        $token = self::parallelTestToken();
        if ($token === null) {
            $this->markTestSkipped('Not running under paratest (TEST_TOKEN unset)');
        }

        // Act
        $phpunitDatabase   = config('database.connections.phpunit.database');
        $combatlogDatabase = config('database.connections.combatlog.database');

        // Assert
        $this->assertStringEndsWith(sprintf('_%s', $token), $phpunitDatabase);
        $this->assertStringEndsWith(sprintf('_%s', $token), $combatlogDatabase);
        $this->assertStringEndsWith(sprintf('%s:', $token), config('database.redis.options.prefix'));
    }
}
