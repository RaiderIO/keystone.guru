<?php

namespace App\Service\Cache\Redis;

use Illuminate\Redis\Connections\Connection;

class PHPRedisService implements RedisServiceInterface
{
    /**
     * @param mixed ...$params
     */
    public function rawCommand(Connection $redis, string $command, ...$params): mixed
    {
        $client = $redis->client();

        return $client->rawCommand($command, ...$params);
    }
}
