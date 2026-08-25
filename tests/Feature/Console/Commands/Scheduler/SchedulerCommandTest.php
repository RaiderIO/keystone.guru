<?php

namespace Tests\Feature\Console\Commands\Scheduler;

use App\Models\Telemetry\TelemetryMetric;
use Illuminate\Console\Command;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Feature\Console\Commands\Scheduler\Fixtures\SchedulerCommandStub;
use Tests\TestCases\PublicTestCase;
use TypeError;

#[Group('Console')]
#[Group('Scheduler')]
final class SchedulerCommandTest extends PublicTestCase
{
    protected function tearDown(): void
    {
        // Every trackTime() call - including the failing ones above - records a telemetry row for the stub
        TelemetryMetric::query()
            ->where('measurement', TelemetryMetric::MEASUREMENT_SCHEDULER)
            ->where('name', 'test:schedulercommandstub')
            ->delete();

        parent::tearDown();
    }

    #[Test]
    public function trackTime_givenSuccessfulCallable_recordsSuccessfulCommandRun(): void
    {
        // Arrange
        SchedulerCommandStub::$callable = static fn(): int => Command::SUCCESS;
        Artisan::registerCommand(new SchedulerCommandStub());

        // Act
        $this->artisan('test:schedulercommandstub')
            ->assertExitCode(Command::SUCCESS);

        // Assert
        /** @var TelemetryMetric|null $telemetryMetric */
        $telemetryMetric = TelemetryMetric::query()
            ->where('measurement', TelemetryMetric::MEASUREMENT_SCHEDULER)
            ->where('name', 'test:schedulercommandstub')
            ->first();

        $this->assertNotNull($telemetryMetric);
        $this->assertTrue($telemetryMetric->success);
        $this->assertGreaterThanOrEqual(0, $telemetryMetric->value);
    }

    #[Test]
    public function trackTime_givenCallableThrows_recordsFailedCommandRun(): void
    {
        // Arrange
        SchedulerCommandStub::$callable = static function (): int {
            throw new RuntimeException('Something went wrong');
        };
        Artisan::registerCommand(new SchedulerCommandStub());

        // Act
        $this->artisan('test:schedulercommandstub')
            ->assertExitCode(Command::FAILURE);

        // Assert
        /** @var TelemetryMetric|null $telemetryMetric */
        $telemetryMetric = TelemetryMetric::query()
            ->where('measurement', TelemetryMetric::MEASUREMENT_SCHEDULER)
            ->where('name', 'test:schedulercommandstub')
            ->first();

        $this->assertNotNull($telemetryMetric);
        $this->assertFalse($telemetryMetric->success);
    }

    #[Test]
    public function trackTime_givenCallableReturnsFailure_recordsFailedCommandRun(): void
    {
        // Arrange
        SchedulerCommandStub::$callable = static fn(): int => Command::FAILURE;
        Artisan::registerCommand(new SchedulerCommandStub());

        // Act
        $this->artisan('test:schedulercommandstub')
            ->assertExitCode(Command::FAILURE);

        // Assert - a non-zero exit code without a throw must still count as a failed run
        /** @var TelemetryMetric|null $telemetryMetric */
        $telemetryMetric = TelemetryMetric::query()
            ->where('measurement', TelemetryMetric::MEASUREMENT_SCHEDULER)
            ->where('name', 'test:schedulercommandstub')
            ->first();

        $this->assertNotNull($telemetryMetric);
        $this->assertFalse($telemetryMetric->success);
    }

    #[Test]
    public function trackTime_givenCallableThrows_reportsThrowableAndReturnsFailure(): void
    {
        // Arrange
        $exception = new RuntimeException('Something went wrong');

        $exceptionHandler = $this->createMockPublic(ExceptionHandler::class);
        $exceptionHandler->expects($this->once())
            ->method('report')
            ->with($exception);
        $this->app->instance(ExceptionHandler::class, $exceptionHandler);

        SchedulerCommandStub::$callable = static function () use ($exception): int {
            throw $exception;
        };
        Artisan::registerCommand(new SchedulerCommandStub());

        // Act & Assert
        $this->artisan('test:schedulercommandstub')
            ->expectsOutputToContain('Something went wrong')
            ->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function trackTime_givenCallableThrowsError_reportsThrowableAndReturnsFailure(): void
    {
        // Arrange
        $error = new TypeError('Not an Exception');

        $exceptionHandler = $this->createMockPublic(ExceptionHandler::class);
        $exceptionHandler->expects($this->once())
            ->method('report')
            ->with($error);
        $this->app->instance(ExceptionHandler::class, $exceptionHandler);

        // An Error is not an Exception - it used to escape trackTime() entirely and must now be reported like one
        SchedulerCommandStub::$callable = static function () use ($error): int {
            throw $error;
        };
        Artisan::registerCommand(new SchedulerCommandStub());

        // Act & Assert
        $this->artisan('test:schedulercommandstub')
            ->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function trackTime_givenCallableReturnsFailure_returnsFailure(): void
    {
        // Arrange
        SchedulerCommandStub::$callable = static fn(): int => Command::FAILURE;
        Artisan::registerCommand(new SchedulerCommandStub());

        // Act & Assert
        $this->artisan('test:schedulercommandstub')
            ->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function trackTime_givenCallableReturnsNothing_returnsSuccess(): void
    {
        // Arrange
        SchedulerCommandStub::$callable = static function (): void {
        };
        Artisan::registerCommand(new SchedulerCommandStub());

        // Act & Assert
        $this->artisan('test:schedulercommandstub')
            ->assertExitCode(Command::SUCCESS);
    }
}
