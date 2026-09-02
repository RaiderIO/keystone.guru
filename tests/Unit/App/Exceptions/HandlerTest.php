<?php

namespace Tests\Unit\App\Exceptions;

use App\Exceptions\Handler;
use App\Exceptions\Logging\HandlerLoggingInterface;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

#[Group('Exceptions')]
#[Group('Handler')]
class HandlerTest extends PublicTestCase
{
    /**
     * The async ContextEvent broadcast job (killzone/route/mapping-version presence updates) is
     * reported to Sentry on every queue attempt, even ones a retry later succeeds on - a DNS lookup
     * failure reaching the broadcast server is a transient environment issue (#4341), not an
     * application defect, so it must never reach the reportable pipeline.
     */
    #[Test]
    public function report_GivenBroadcastExceptionFromDnsResolutionFailure_ShouldNotInvokeReportCallbacks(): void
    {
        // Arrange
        $handler       = app()->make(Handler::class);
        $callbackCalls = 0;
        $handler->reportable(function (BroadcastException $e) use (&$callbackCalls) {
            $callbackCalls++;

            return false;
        });

        // Act
        $handler->report(new BroadcastException('Pusher error: cURL error 6: Could not resolve host: staging-reverb.svc.local.'));

        // Assert
        self::assertSame(0, $callbackCalls);
    }

    /**
     * A genuine (non-DNS) broadcast failure - e.g. bad credentials or an oversized payload - must
     * still surface normally; only the specific transient DNS-resolution failure is suppressed.
     */
    #[Test]
    public function report_GivenBroadcastExceptionFromOtherCause_ShouldInvokeReportCallbacks(): void
    {
        // Arrange
        $handler       = app()->make(Handler::class);
        $callbackCalls = 0;
        $handler->reportable(function (BroadcastException $e) use (&$callbackCalls) {
            $callbackCalls++;

            return false;
        });

        // Act
        $handler->report(new BroadcastException('Pusher error: invalid credentials.'));

        // Assert
        self::assertSame(1, $callbackCalls);
    }

    /**
     * A crawler requesting a URL whose percent-encoding is truncated makes ValidatePathEncoding throw
     * MalformedUrlException - a 400 HttpException subclass. $dontReport is matched by exact class, so
     * the subclass must be listed itself or the 400 is logged as an uncaught error (#4438).
     */
    #[Test]
    public function report_givenRequestWithTruncatedPercentEncodedPath_doesNotLogUncaughtException(): void
    {
        // Arrange
        $this->markApplicationAsNotRunningInConsole();
        $handlerLogging = $this->createMock(HandlerLoggingInterface::class);
        $handlerLogging->expects(self::never())->method('uncaughtException');
        $this->instance(HandlerLoggingInterface::class, $handlerLogging);

        // Act
        $response = $this->get('/route/%E0%A4');

        // Assert
        $response->assertBadRequest();
    }

    /**
     * Control for the test above: an exception outside $dontReport still reaches uncaughtException
     * once the console guard is lifted, so a "never" expectation there is meaningful.
     */
    #[Test]
    public function report_givenUnlistedExceptionOutsideConsole_logsUncaughtException(): void
    {
        // Arrange
        $this->markApplicationAsNotRunningInConsole();
        $handlerLogging = $this->createMock(HandlerLoggingInterface::class);
        $handlerLogging->expects(self::once())->method('uncaughtException');
        $this->instance(HandlerLoggingInterface::class, $handlerLogging);
        $handler = app()->make(Handler::class);
        $handler->reportable(static fn(RuntimeException $e): bool => false);

        // Act
        $handler->report(new RuntimeException('boom'));

        // Assert - the mock expectation
    }

    /**
     * Handler::report() skips HandlerLogging entirely under PHPUnit because PHP_SAPI is cli; the
     * answer is cached on the application, so flip the cached value for this app instance.
     */
    private function markApplicationAsNotRunningInConsole(): void
    {
        new ReflectionProperty(Application::class, 'isRunningInConsole')->setValue($this->app, false);
    }
}
