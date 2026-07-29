<?php

namespace Tests\Feature\Console\Commands\Scheduler;

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
