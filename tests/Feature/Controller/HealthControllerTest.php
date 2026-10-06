<?php

namespace Tests\Feature\Controller;

use App\Service\Health\HealthCheckServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Health')]
final class HealthControllerTest extends PublicTestCase
{
    #[Test]
    public function check_givenHealthyDependencies_returnsOk(): void
    {
        // Act
        $response = $this->get(route('health.app'));

        // Assert
        $response->assertOk();
        $response->assertExactJson([
            'status' => 'ok',
            'checks' => [
                'database' => 'ok',
                'redis'    => 'ok',
            ],
        ]);
        $response->assertCookieMissing(config('session.cookie'));
    }

    #[Test]
    public function check_givenDatabaseUnreachable_returnsServiceUnavailableNamingDatabase(): void
    {
        // Arrange
        $this->bindHealthCheckService(databaseReachable: false, redisReachable: true);

        // Act
        $response = $this->get(route('health.app'));

        // Assert
        $response->assertStatus(StatusCode::SERVICE_UNAVAILABLE);
        $response->assertExactJson([
            'status' => 'fail',
            'checks' => [
                'database' => 'fail',
                'redis'    => 'ok',
            ],
        ]);
    }

    #[Test]
    public function check_givenRedisUnreachable_returnsServiceUnavailableNamingRedis(): void
    {
        // Arrange
        $this->bindHealthCheckService(databaseReachable: true, redisReachable: false);

        // Act
        $response = $this->get(route('health.app'));

        // Assert
        $response->assertStatus(StatusCode::SERVICE_UNAVAILABLE);
        $response->assertExactJson([
            'status' => 'fail',
            'checks' => [
                'database' => 'ok',
                'redis'    => 'fail',
            ],
        ]);
    }

    private function bindHealthCheckService(bool $databaseReachable, bool $redisReachable): void
    {
        $healthCheckService = $this->createMockPublic(HealthCheckServiceInterface::class);
        $healthCheckService->method('isDatabaseReachable')->willReturn($databaseReachable);
        $healthCheckService->method('isRedisReachable')->willReturn($redisReachable);

        $this->app->instance(HealthCheckServiceInterface::class, $healthCheckService);
    }
}
