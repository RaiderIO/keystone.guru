<?php

namespace Tests\Fixtures;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastException;

/**
 * A broadcaster whose first $failingPublishes publishes throw, as when Reverb is unreachable, and that counts every
 * publish it was asked for.
 */
class FailingBroadcaster extends Broadcaster
{
    public int $publishCount = 0;

    public function __construct(private readonly int $failingPublishes)
    {
    }

    public function auth($request): mixed
    {
        return null;
    }

    public function validAuthenticationResponse($request, $result): mixed
    {
        return null;
    }

    /**
     * @param array<int, mixed>    $channels
     * @param array<string, mixed> $payload
     */
    public function broadcast(array $channels, $event, array $payload = []): void
    {
        if (++$this->publishCount <= $this->failingPublishes) {
            throw new BroadcastException('Reverb is down');
        }
    }
}
