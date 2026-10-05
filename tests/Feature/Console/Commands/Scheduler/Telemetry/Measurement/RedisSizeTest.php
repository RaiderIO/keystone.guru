<?php

namespace Tests\Feature\Console\Commands\Scheduler\Telemetry\Measurement;

use App\Console\Commands\Scheduler\Telemetry\Measurement\RedisSize;
use App\Models\Telemetry\TelemetryMetric;
use App\Service\Telemetry\Dtos\TelemetryDataPoint;
use Illuminate\Support\Facades\Redis;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Scheduler')]
#[Group('Telemetry')]
final class RedisSizeTest extends PublicTestCase
{
    #[Test]
    public function getDataPoints_returnsOneDataPointPerDistinctConfiguredDatabase(): void
    {
        // Arrange
        $distinctDatabases = [];
        foreach (config('database.redis') as $connectionConfig) {
            if (is_array($connectionConfig) && isset($connectionConfig['database'])) {
                $distinctDatabases[(string)$connectionConfig['database']] = true;
            }
        }

        // Act
        $dataPoints = (new RedisSize())->getDataPoints();

        // Assert
        $this->assertCount(count($distinctDatabases), $dataPoints);

        $seenTags = [];
        foreach ($dataPoints as $dataPoint) {
            $this->assertInstanceOf(TelemetryDataPoint::class, $dataPoint);
            $this->assertSame(TelemetryMetric::MEASUREMENT_REDIS, $dataPoint->measurement);
            $this->assertSame('keys', $dataPoint->name);
            $this->assertMatchesRegularExpression('/^db\d+$/', (string)$dataPoint->tag);
            $this->assertGreaterThanOrEqual(0.0, $dataPoint->value);

            $seenTags[$dataPoint->tag] = true;
        }

        // Every distinct database is represented exactly once (no duplicate tags).
        $this->assertCount(count($distinctDatabases), $seenTags);
    }

    #[Test]
    public function getDataPoints_givenConnectionsSharingADatabase_samplesItOnceThroughTheFirstConnection(): void
    {
        // Arrange
        config(['database.redis' => [
            'client'  => 'phpredis',
            'options' => ['prefix' => 'test'],
            'default' => ['database' => '0'],
            'cache'   => ['database' => '1'],
            'session' => ['database' => '0'],
        ]]);

        $keyCountsByConnectionName = ['default' => 5, 'cache' => 7, 'session' => 99];
        Redis::shouldReceive('connection')->andReturnUsing(static function (string $connectionName) use ($keyCountsByConnectionName) {
            $connection = Mockery::mock();
            $connection->shouldReceive('dbsize')->andReturn($keyCountsByConnectionName[$connectionName]);

            return $connection;
        });

        // Act
        $dataPoints = (new RedisSize())->getDataPoints();

        // Assert
        $keyCountsByTag = [];
        foreach ($dataPoints as $dataPoint) {
            $keyCountsByTag[$dataPoint->tag] = $dataPoint->value;
        }

        $this->assertSame(['db0' => 5.0, 'db1' => 7.0], $keyCountsByTag);
    }
}
