<?php

namespace Tests\Unit\App\Logging;

use App\Logging\StructuredLogging;
use Illuminate\Support\Facades\Context;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Pins the two properties the Sentry triage workflow depends on, both of which would break silently:
 *
 * 1. The structured context survives. StructuredLogging mirrors every open start() group into Laravel's Context,
 *    Laravel's ContextLogProcessor merges the Context repository into LogRecord->extra, and SentryHandler copies
 *    every extra onto the scope - which is how trace_id and the 'structured:*' groups end up on the issue.
 * 2. A log-derived event's message is the bare 'ClassLogging::method'. Sentry groups message events by their
 *    message, so this yields exactly one stable issue per log site. Enabling attach_stacktrace would group by stack
 *    trace instead and fragment one log site into one issue per call path.
 *
 * Logged through a real StructuredLogging instance and the configured sentry channel, so the test fails when any
 * link of that chain stops carrying the context.
 */
#[Group('Logging')]
final class SentryStructuredContextTest extends PublicTestCase
{
    use ResolvesDsnShapedSentryChannel;

    private const string LOGGING_CLASS = 'App\\Service\\CombatLog\\Logging\\ProcessCombatLogSegmentsLogging';

    #[Test]
    public function sentryChannel_givenStructuredLogInsideAStartedGroup_capturesExtrasAndBareMessage(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'production']);
        $transport = $this->bindStubHub();
        $this->sentryChannel();

        Context::add('trace_id', 'f3776964-f303-4476-8d78-b6f1f17b3f18');

        $log = new TestableStructuredLogging(app('log'));

        try {
            StructuredLogging::setChannel(self::DSN_SHAPED_CHANNEL);

            // Act
            $log->start(sprintf('%s::handleStart', self::LOGGING_CLASS), ['runId' => 42015954]);
            $log->error(sprintf('%s::handleSegmentsNotAvailable', self::LOGGING_CLASS));
            $log->end(sprintf('%s::handleEnd', self::LOGGING_CLASS));
        } finally {
            StructuredLogging::setChannel(null);
        }

        // Assert - start() and end() log at info, below the channel's level
        self::assertCount(1, $transport->capturedEvents);

        $event = $transport->capturedEvents[0];

        self::assertSame(
            'ProcessCombatLogSegmentsLogging::handleSegmentsNotAvailable',
            $event->getMessage(),
            'The event message must stay the bare method name, or Sentry stops grouping one log site into one issue.',
        );

        $extra = $event->getExtra();
        self::assertSame(
            'f3776964-f303-4476-8d78-b6f1f17b3f18',
            $extra['trace_id'] ?? null,
            'trace_id must reach Sentry so an issue can be traced back.',
        );
        self::assertSame(['runId' => 42015954], $extra['structured:processcombatlogsegmentslogging::handle'] ?? null);
    }
}
