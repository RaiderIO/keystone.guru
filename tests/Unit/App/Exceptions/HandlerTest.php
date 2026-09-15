<?php

namespace Tests\Unit\App\Exceptions;

use App\Exceptions\Handler;
use App\Exceptions\Logging\HandlerLoggingInterface;
use App\Exceptions\ThumbnailRenderFailedException;
use App\Service\CombatLog\Exceptions\CombatLogSegmentDownloadFailedException;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
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
     * The thumbnail job throws this only to make the queue worker retry; ThumbnailService has already logged the
     * failed render, so it must never reach the reportable pipeline Sentry hooks into.
     */
    #[Test]
    public function report_givenThumbnailRenderFailedException_doesNotInvokeReportCallbacks(): void
    {
        // Arrange
        $handler       = app()->make(Handler::class);
        $callbackCalls = 0;
        $handler->reportable(function (ThumbnailRenderFailedException $e) use (&$callbackCalls) {
            $callbackCalls++;

            return false;
        });

        // Act
        $handler->report(new ThumbnailRenderFailedException('Failed to create thumbnail for dungeon route 1 floor 1 on attempt 1'));

        // Assert
        self::assertSame(0, $callbackCalls);
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
     * A run's segment failing to download is expected and costs nothing - the run is retried or
     * skipped and its budget given back - so it must not page Sentry at error level like a genuine
     * defect would. bootstrap/app.php maps it to warning via $exceptions->level(); this asserts
     * that mapping actually reaches the bound handler instance rather than testing bootstrap/app.php
     * directly.
     */
    #[Test]
    public function level_givenCombatLogSegmentDownloadFailedException_isMappedToWarning(): void
    {
        // Arrange
        $handler = app()->make(Handler::class);

        // Act
        $levels = new ReflectionProperty($handler::class, 'levels')->getValue($handler);

        // Assert
        self::assertSame(LogLevel::WARNING, $levels[CombatLogSegmentDownloadFailedException::class] ?? null);
    }

    /**
     * InvalidSignatureException is a 403 HttpException subclass, so the exact-class $dontReport check lets it
     * through; it must land on the warning-level invalidSignature record instead of the error-level one.
     */
    #[Test]
    public function report_givenInvalidSignatureExceptionForUrlWithoutSignature_logsInvalidSignatureInsteadOfUncaughtException(): void
    {
        // Arrange
        $this->markApplicationAsNotRunningInConsole();
        $this->app->instance('request', Request::create('/ajax/i2zrVoe/mdtExport'));
        $handlerLogging = $this->createMock(HandlerLoggingInterface::class);
        $handlerLogging->expects(self::never())->method('uncaughtException');
        $handlerLogging->expects(self::once())
            ->method('invalidSignature')
            ->with(self::anything(), 'http://localhost/ajax/i2zrVoe/mdtExport', null, null, false, false);
        $this->instance(HandlerLoggingInterface::class, $handlerLogging);
        $handler = app()->make(Handler::class);

        // Act
        $handler->report(new InvalidSignatureException());

        // Assert - the mock expectations
    }

    #[Test]
    public function report_givenInvalidSignatureExceptionForExpiredSignedUrl_logsInvalidSignatureAsExpired(): void
    {
        // Arrange
        $this->markApplicationAsNotRunningInConsole();
        $expires = now()->subHour()->getTimestamp();
        $this->app->instance('request', Request::create(sprintf('/ajax/i2zrVoe/mdtExport?expires=%d&signature=abc', $expires)));
        $handlerLogging = $this->createMock(HandlerLoggingInterface::class);
        $handlerLogging->expects(self::once())
            ->method('invalidSignature')
            ->with(self::anything(), self::anything(), null, null, true, true);
        $this->instance(HandlerLoggingInterface::class, $handlerLogging);
        $handler = app()->make(Handler::class);

        // Act
        $handler->report(new InvalidSignatureException());

        // Assert - the mock expectation
    }

    #[Test]
    public function report_givenInvalidSignatureExceptionForTamperedUnexpiredSignedUrl_logsInvalidSignatureAsNotExpired(): void
    {
        // Arrange
        $this->markApplicationAsNotRunningInConsole();
        $expires = now()->addHour()->getTimestamp();
        $this->app->instance('request', Request::create(sprintf('/ajax/i2zrVoe/mdtExport?expires=%d&signature=abc', $expires)));
        $handlerLogging = $this->createMock(HandlerLoggingInterface::class);
        $handlerLogging->expects(self::once())
            ->method('invalidSignature')
            ->with(self::anything(), self::anything(), null, null, true, false);
        $this->instance(HandlerLoggingInterface::class, $handlerLogging);
        $handler = app()->make(Handler::class);

        // Act
        $handler->report(new InvalidSignatureException());

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
