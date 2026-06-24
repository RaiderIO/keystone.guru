<?php

namespace App\Service\Cache\Redis;

use Illuminate\Redis\Connections\Connection;

class PRedisService implements RedisServiceInterface
{
    /**
     * @param mixed ...$params
     */
    public function rawCommand(Connection $redis, string $command, ...$params): mixed
    {
        return $redis->command('SCAN', ...$params);
    }
}
