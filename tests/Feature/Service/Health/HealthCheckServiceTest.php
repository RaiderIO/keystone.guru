<?php

namespace Tests\Feature\Service\Health;

use App\Service\Health\HealthCheckService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('Health')]
final class HealthCheckServiceTest extends TestCase
{
    /** Nothing listens on port 1, so a connection to it is refused at once instead of timing out. */
    private const string UNREACHABLE_HOST = '127.0.0.1';
    private const int    UNREACHABLE_PORT = 1;

    #[Test]
    public function isDatabaseReachable_givenReachableDatabase_returnsTrue(): void
    {
        // Act
        $reachable = new HealthCheckService()->isDatabaseReachable();

        // Assert
        $this->assertTrue($reachable);
    }

    #[Test]
    public function isDatabaseReachable_givenUnreachableDatabase_returnsFalse(): void
    {
        // Arrange
        $configKey      = sprintf('database.connections.%s', config('database.default'));
        $originalConfig = config($configKey);

        try {
            config([
                sprintf('%s.host', $configKey) => self::UNREACHABLE_HOST,
                sprintf('%s.port', $configKey) => self::UNREACHABLE_PORT,
            ]);
            DB::purge();

            // Act
            $reachable = new HealthCheckService()->isDatabaseReachable();

            // Assert
            $this->assertFalse($reachable);
        } finally {
            config([$configKey => $originalConfig]);
            DB::purge();
        }
    }

    #[Test]
    public function isRedisReachable_givenReachableRedis_returnsTrue(): void
    {
        // Act
        $reachable = new HealthCheckService()->isRedisReachable();

        // Assert
        $this->assertTrue($reachable);
    }

    #[Test]
    public function isRedisReachable_givenUnreachableRedis_returnsFalse(): void
    {
        // Arrange
        $originalConfig = config('database.redis.default');

        try {
            config([
                'database.redis.default.host' => self::UNREACHABLE_HOST,
                'database.redis.default.port' => self::UNREACHABLE_PORT,
                'database.redis.default.url'  => null,
            ]);
            Redis::purge('default');

            // Act
            $reachable = new HealthCheckService()->isRedisReachable();

            // Assert
            $this->assertFalse($reachable);
        } finally {
            config(['database.redis.default' => $originalConfig]);
            Redis::purge('default');
        }
    }
}
