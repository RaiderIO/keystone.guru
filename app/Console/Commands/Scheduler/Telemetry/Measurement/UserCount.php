<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;


use App\User;
use InfluxDB\Point;

class UserCount extends Measurement
{
    /**
     * @inheritDoc
     */
    function getPoints(): array
    {
        return [
            new Point(
                'user_count',
                null,
                $this->getTags(),
                [
                    'all'          => User::count(),
                    'keystoneguru' => User::whereNull('oauth_id')->count(),
                    'discord'      => User::where('oauth_id', 'LIKE', '%@discord')->count(),
                    'google'       => User::where('oauth_id', 'LIKE', '%@google')->count(),
                    'battlenet'    => User::where('oauth_id', 'LIKE', '%@battlenet')->count(),
                ],
                time()
            ),
        ];
    }
}
