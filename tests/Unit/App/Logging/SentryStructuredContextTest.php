<?php

namespace Tests\Unit\App\Logging;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sentry\ClientBuilder;
use Sentry\Event;
use Sentry\Laravel\SentryHandler;
use Sentry\Options;
use Sentry\State\Hub;
use Sentry\Transport\Result;
use Sentry\Transport\ResultStatus;
use Sentry\Transport\TransportInterface;
use Tests\TestCases\PublicTestCase;

/**
 * Pins the two properties the Sentry triage workflow depends on, both of which come for free today and would break
 * silently:
 *
 * 1. The structured context survives. Laravel's ContextLogProcessor merges the Context repository into
 *    LogRecord->extra, and SentryHandler copies every extra onto the scope - which is how trace_id and the
 *    'structured:*' groups end up on the issue.
 * 2. A log-derived event's message is the bare 'ClassLogging::method'. Sentry groups message events by their
 *    message, so this yields exactly one stable issue per log site. Enabling attach_stacktrace would group by stack
 *    trace instead and fragment one log site into one issue per call path.
 */
#[Group('Logging')]
final class SentryStructuredContextTest extends PublicTestCase
{
    #[Test]
    public function sentryHandler_givenStructuredLogRecord_capturesExtrasAndBareMessage(): void
    {
        // Arrange - an in-memory transport so the assertions run against the Event the client actually built
        $transport = new class implements TransportInterface {
            /** @var array<int, Event> Every event the client handed to this transport, in order */
            public array $capturedEvents = [];

            public function send(Event $event): Result
            {
                $this->capturedEvents[] = $event;

                return new Result(ResultStatus::success(), $event);
            }

            public function close(?int $timeout = null): Result
            {
                return new Result(ResultStatus::success());
            }
        };

        $hub = $this->createHubUsing($transport);

        $record = new LogRecord(
            new DateTimeImmutable(),
            'testing',
            Level::Error,
            'ProcessCombatLogSegmentsLogging::handleSegmentsNotAvailable',
            ['runId' => 42015954, 'depth' => 1],
            [
                'trace_id'                                           => 'f3776964-f303-4476-8d78-b6f1f17b3f18',
                'structured:processcombatlogsegmentslogging::handle' => ['runId' => 42015954],
            ],
        );

        // Act
        (new SentryHandler($hub, Level::Error->value))->handle($record);

        // Assert
        self::assertCount(1, $transport->capturedEvents);

        $event = $transport->capturedEvents[0];

        self::assertSame(
            'ProcessCombatLogSegmentsLogging::handleSegmentsNotAvailable',
            $event->getMessage(),
            'The event message must stay the bare method name, or Sentry stops grouping one log site into one issue.',
        );

        $extra = $event->getExtra();
        self::assertArrayHasKey('trace_id', $extra, 'trace_id must reach Sentry so an issue can be traced back.');
        self::assertArrayHasKey('structured:processcombatlogsegmentslogging::handle', $extra);
    }

    private function createHubUsing(TransportInterface $transport): Hub
    {
        $clientBuilder = new ClientBuilder(new Options([
            'dsn'                  => 'https://publickey@sentry.example.com/1',
            'default_integrations' => false,
        ]));
        $clientBuilder->setTransport($transport);

        return new Hub($clientBuilder->getClient());
    }
}
