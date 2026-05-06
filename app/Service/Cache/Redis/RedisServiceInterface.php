<?php

namespace App\Service\Cache\Redis;

use Illuminate\Redis\Connections\Connection;

interface RedisServiceInterface
{
    public function rawCommand(Connection $redis, string $command, ...$params): mixed;
}
