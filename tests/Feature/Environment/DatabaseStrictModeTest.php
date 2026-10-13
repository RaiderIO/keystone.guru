<?php

namespace Tests\Feature\Environment;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards that every MySQL connection runs in strict mode. Without it MySQL silently truncates over-long strings,
 * coerces invalid values, accepts zero dates and lets a GROUP BY return an arbitrary row's columns, so the data
 * bug surfaces nowhere and no test can catch it.
 */
#[Group('Environment')]
final class DatabaseStrictModeTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function mysqlConnectionProvider(): array
    {
        $connections = require dirname(__DIR__, 3) . '/config/database.php';

        $result = [];
        foreach ($connections['connections'] as $name => $connection) {
            if ($connection['driver'] === 'mysql') {
                $result[$name] = [$name];
            }
        }

        return $result;
    }

    #[Test]
    #[DataProvider('mysqlConnectionProvider')]
    public function strict_givenMysqlConnection_isEnabled(string $connectionName): void
    {
        // Arrange

        // Act
        $strict = config(sprintf('database.connections.%s.strict', $connectionName));

        // Assert
        $this->assertTrue($strict, sprintf('Connection %s does not run in strict mode', $connectionName));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function sessionConnectionProvider(): array
    {
        return [
            'phpunit'   => ['phpunit'],
            'combatlog' => ['combatlog'],
        ];
    }

    #[Test]
    #[DataProvider('sessionConnectionProvider')]
    public function sqlMode_givenTestConnection_containsTheStrictModes(string $connectionName): void
    {
        // Arrange

        // Act
        $sqlMode = explode(',', DB::connection($connectionName)->selectOne('SELECT @@SESSION.sql_mode AS sql_mode')->sql_mode);

        // Assert
        $this->assertContains('STRICT_TRANS_TABLES', $sqlMode);
        $this->assertContains('ONLY_FULL_GROUP_BY', $sqlMode);
        $this->assertContains('NO_ZERO_DATE', $sqlMode);
    }
}
