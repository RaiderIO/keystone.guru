<?php

namespace App\Service\Cache\Logging;

interface CacheServiceLoggingInterface
{

    public function deleteKeysByPatternStart(?int $seconds): void;

    public function deleteKeysByPatternRegexError(string $regex, string $redisKey): void;

    public function deleteKeysByPatternFailedToDeleteAllKeys(int $amount, int $total): void;

    public function deleteKeysByPatternProgress(int $index, int $deletedKeysCount): void;

    public function deleteKeysByPatternEnd(int $deletedKeysCount): void;
}
