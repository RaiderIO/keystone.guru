<?php

namespace App\Service\Cache\Logging;

use App\Logging\RollbarStructuredLogging;
use Exception;

class CacheServiceLogging extends RollbarStructuredLogging implements CacheServiceLoggingInterface
{
    public function rememberFailedToSetCache(string $key, Exception $e): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function rememberFailedToAcquireLock(string $key, Exception $e): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function deleteKeysByPatternStart(?int $seconds): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function deleteKeysByPatternRegexError(string $regex, string $redisKey): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }


    public function deleteKeysByPatternFailedToDeleteAllKeys(int $amount, int $total): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function deleteKeysByPatternProgress(int $index, int $deletedKeysCount): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function deleteKeysByPatternEnd(int $deletedKeysCount): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
