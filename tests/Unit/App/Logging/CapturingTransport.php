<?php

namespace Tests\Unit\App\Logging;

use Sentry\Event;
use Sentry\Transport\Result;
use Sentry\Transport\ResultStatus;
use Sentry\Transport\TransportInterface;

/**
 * In-memory Sentry transport, so tests can assert on the events the client actually built without anything leaving
 * the test run.
 */
class CapturingTransport implements TransportInterface
{
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
}
