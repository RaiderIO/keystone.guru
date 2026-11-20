<?php

namespace App\Service\WowTools\Logging;

use App\Logging\StructuredLogging;

class WowToolsServiceLogging extends StructuredLogging implements WowToolsServiceLoggingInterface
{
    public function getDisplayIdRequestStart(int $npcId): void
    {
        $this->start(__METHOD__, get_defined_vars());
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

    public function getDisplayIdRequestEnd(): void
    {
        $this->end(__METHOD__);
    }
}
