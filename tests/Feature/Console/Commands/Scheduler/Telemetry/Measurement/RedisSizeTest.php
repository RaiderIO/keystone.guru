<?php

namespace Tests\Feature\Console\Commands\Scheduler\Telemetry\Measurement;

use App\Console\Commands\Scheduler\Telemetry\Measurement\RedisSize;
use App\Models\Telemetry\TelemetryMetric;
use App\Service\Telemetry\Dtos\TelemetryDataPoint;
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
}
