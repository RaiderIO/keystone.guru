<?php

namespace App\Service\Cache\Redis;

use Illuminate\Redis\Connections\Connection;

interface RedisServiceInterface
{
    /**
     * @param mixed ...$params
     */
    public function rawCommand(Connection $redis, string $command, ...$params): mixed;
}
