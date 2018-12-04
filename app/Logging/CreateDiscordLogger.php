<?php

namespace App\Logging;

use Monolog\Logger;
use DiscordHandler\DiscordHandler;

class CreateDiscordLogger
{
    /**
     * Create a custom Discord Monolog instance.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $log = new Logger('discord');
        $log->pushHandler(new DiscordHandler([$config['url']], $config['level']));

        return $log;
    }
}