<?php

namespace App\Service\Cache\Logging;

interface CacheServiceLoggingInterface
{

    public function clearIdleKeysStart(?int $seconds): void;

    public function clearIdleKeysRegexError(string $regex, string $redisKey): void;

    public function clearIdleKeysFailedToDeleteAllKeys(int $amount, int $total): void;

    public function clearIdleKeysProgress(int $index, int $deletedKeysCount): void;

    public function clearIdleKeysEnd(): void;
}
