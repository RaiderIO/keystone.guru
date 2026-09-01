<?php

namespace Tests\Unit\App\Exceptions;

use App\Exceptions\Handler;
use Illuminate\Broadcasting\BroadcastException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
}
