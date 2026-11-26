<?php

namespace Tests\Fixtures\Traits;

use Illuminate\Support\Facades\Broadcast;
use Tests\Fixtures\FailingBroadcaster;
use Tests\TestCase;

/**
 * @mixin TestCase
 */
trait FailsBroadcasts
{
    /**
     * Make the default broadcast connection one whose first $failingPublishes publishes throw.
     */
    private function failBroadcasts(int $failingPublishes = PHP_INT_MAX): FailingBroadcaster
    {
        $broadcaster = new FailingBroadcaster($failingPublishes);
        Broadcast::extend('failing', static fn(): FailingBroadcaster => $broadcaster);
        config([
            'broadcasting.connections.failing' => ['driver' => 'failing'],
            'broadcasting.default'             => 'failing',
        ]);

        return $broadcaster;
    }
}
