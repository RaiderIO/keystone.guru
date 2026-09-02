<?php

namespace Tests\Feature\Service\Telemetry;

use App\Models\Telemetry\TelemetryMetric;
use App\Repositories\Interfaces\Telemetry\TelemetryMetricRepositoryInterface;
use App\Service\Telemetry\Dtos\TelemetryDataPoint;
use App\Service\Telemetry\Logging\TelemetryServiceLoggingInterface;
use App\Service\Telemetry\TelemetryService;
use App\Service\Telemetry\TelemetryServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

#[Group('Telemetry')]
final class TelemetryServiceTest extends PublicTestCase
{
    private const string TEST_COMMAND_NAME = 'test:telemetryservicetest';

    #[Test]
    public function recordCommandRun_givenSuccessfulRun_persistsSuccessfulRow(): void
    {
        // Arrange
        $telemetryService = $this->app->make(TelemetryServiceInterface::class);

        try {
            // Act
            $telemetryService->recordCommandRun(self::TEST_COMMAND_NAME, 1234.56, true);

            // Assert
            /** @var TelemetryMetric|null $telemetryMetric */
            $telemetryMetric = TelemetryMetric::query()
                ->where('measurement', TelemetryMetric::MEASUREMENT_SCHEDULER)
                ->where('name', self::TEST_COMMAND_NAME)
                ->first();

            $this->assertNotNull($telemetryMetric);
            $this->assertTrue($telemetryMetric->success);
            $this->assertEqualsWithDelta(1234.56, $telemetryMetric->value, 0.01);
            $this->assertNull($telemetryMetric->tag);
        } finally {
            $this->deleteTestRows();
        }
    }

    #[Test]
    public function recordCommandRun_givenFailedRun_persistsSuccessFalseRow(): void
    {
        // Arrange
        $telemetryService = $this->app->make(TelemetryServiceInterface::class);

        try {
            // Act
            $telemetryService->recordCommandRun(self::TEST_COMMAND_NAME, 42.0, false);

            // Assert
            /** @var TelemetryMetric|null $telemetryMetric */
            $telemetryMetric = TelemetryMetric::query()
                ->where('measurement', TelemetryMetric::MEASUREMENT_SCHEDULER)
                ->where('name', self::TEST_COMMAND_NAME)
                ->first();

            $this->assertNotNull($telemetryMetric);
            $this->assertFalse($telemetryMetric->success);
        } finally {
            $this->deleteTestRows();
        }
    }

    #[Test]
    public function recordDataPoints_givenMultipleDataPoints_persistsAllRows(): void
    {
        // Arrange
        $telemetryService = $this->app->make(TelemetryServiceInterface::class);

        try {
            // Act
            $telemetryService->recordDataPoints([
                new TelemetryDataPoint(TelemetryMetric::MEASUREMENT_USER_COUNT, self::TEST_COMMAND_NAME, 10),
                new TelemetryDataPoint(TelemetryMetric::MEASUREMENT_QUEUE, self::TEST_COMMAND_NAME, 5, 'default'),
            ]);

            // Assert
            $this->assertSame(2, TelemetryMetric::query()->where('name', self::TEST_COMMAND_NAME)->count());
            $this->assertSame(
                1,
                TelemetryMetric::query()
                    ->where('name', self::TEST_COMMAND_NAME)
                    ->where('tag', 'default')
                    ->count(),
            );
        } finally {
            $this->deleteTestRows();
        }
    }

    #[Test]
    public function recordDataPoints_givenRepositoryThrows_logsFailureAndDoesNotThrow(): void
    {
        // Arrange
        $telemetryMetricRepository = $this->createMockPublic(TelemetryMetricRepositoryInterface::class);
        $telemetryMetricRepository->expects($this->once())
            ->method('insertBatch')
            ->willThrowException(new RuntimeException('Database is down'));

        $log = $this->createMockPublic(TelemetryServiceLoggingInterface::class);
        $log->expects($this->once())
            ->method('recordDataPointsFailed')
            ->with(1, 'Database is down');

        $telemetryService = new TelemetryService($telemetryMetricRepository, $log);

        // Act & Assert - no exception may escape
        $telemetryService->recordDataPoints([
            new TelemetryDataPoint(TelemetryMetric::MEASUREMENT_SCHEDULER, self::TEST_COMMAND_NAME, 1),
        ]);
    }

    #[Test]
    public function recordDataPoints_givenNoDataPoints_doesNotInsert(): void
    {
        // Arrange
        $telemetryMetricRepository = $this->createMockPublic(TelemetryMetricRepositoryInterface::class);
        $telemetryMetricRepository->expects($this->never())
            ->method('insertBatch');

        $log = $this->createMockPublic(TelemetryServiceLoggingInterface::class);

        $telemetryService = new TelemetryService($telemetryMetricRepository, $log);

        // Act & Assert
        $telemetryService->recordDataPoints([]);
    }

    private function deleteTestRows(): void
    {
        TelemetryMetric::query()->where('name', self::TEST_COMMAND_NAME)->delete();
    }
}
