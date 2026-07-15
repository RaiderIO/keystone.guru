<?php

namespace App\Service\WowTools\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;
use Closure;

class WowToolsServiceLogging extends StructuredLogging implements WowToolsServiceLoggingInterface
{
    use InteractsWithRollbar;

    /**
     * @template T
     *
     * @param Closure(): T $callback
     *
     * @return T
     */
    public function getDisplayIdRequest(int $npcId, Closure $callback): mixed
    {
        return $this->wrapLog(__METHOD__, get_defined_vars(), $callback);
    }

    public function getDisplayIdInvalidResponse(): void
    {
        $this->error(__METHOD__);
    }

    public function getDisplayIdRequestError(string $error): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getDisplayIdRequestResult(int $displayInfoId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function getDisplayIdRequestResultUnableFindCreateDisplayInfoID(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }
}
