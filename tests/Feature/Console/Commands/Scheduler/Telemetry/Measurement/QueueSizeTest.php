<?php

namespace Tests\Feature\Console\Commands\Scheduler\Telemetry\Measurement;

use App\Console\Commands\Scheduler\Telemetry\Measurement\QueueSize;
use App\Jobs\DropCaches;
use App\Jobs\Enums\QueueName;
use App\Models\Telemetry\TelemetryMetric;
use App\Service\Telemetry\Dtos\TelemetryDataPoint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Scheduler')]
#[Group('Telemetry')]
final class QueueSizeTest extends PublicTestCase
{
    #[Test]
    public function getDataPoints_givenQueuedJobs_returnsOneDataPointPerQueueName(): void
    {
        // Arrange
        Queue::fake();
        DropCaches::dispatch();
        DropCaches::dispatch();

        // Act
        $dataPoints = (new QueueSize())->getDataPoints();

        // Assert
        $sizesByQueue = [];
        foreach ($dataPoints as $dataPoint) {
            $this->assertInstanceOf(TelemetryDataPoint::class, $dataPoint);
            $this->assertSame(TelemetryMetric::MEASUREMENT_QUEUE, $dataPoint->measurement);
            $this->assertSame('size', $dataPoint->name);

            $sizesByQueue[$dataPoint->tag] = $dataPoint->value;
        }

        $expectedSizes = [];
        foreach (QueueName::cases() as $queueName) {
            $expectedSizes[$queueName->queueName()] = $queueName === QueueName::LongRunning ? 2 : 0;
        }

        $this->assertEquals($expectedSizes, $sizesByQueue);
    }

    #[Test]
    public function getDataPoints_givenQueueSizeThrows_skipsThatQueueAndLogsWarning(): void
    {
        // Arrange
        $missingQueue = QueueName::Thumbnail->queueName();
        Queue::shouldReceive('size')->andReturnUsing(static function (string $queueName) use ($missingQueue): int {
            if ($queueName === $missingQueue) {
                throw new RuntimeException('The specified queue does not exist');
            }

            return 3;
        });
        $logSpy = Log::spy();

        // Act
        $dataPoints = (new QueueSize())->getDataPoints();

        // Assert
        $measuredQueues = array_map(static fn(TelemetryDataPoint $dataPoint) => $dataPoint->tag, $dataPoints);
        $this->assertCount(count(QueueName::cases()) - 1, $dataPoints);
        $this->assertNotContains($missingQueue, $measuredQueues);
        foreach ($dataPoints as $dataPoint) {
            $this->assertEquals(3, $dataPoint->value);
        }

        $logSpy->shouldHaveReceived('warning', [Mockery::on(static fn(string $message) => str_contains($message, $missingQueue))]);
    }
}
