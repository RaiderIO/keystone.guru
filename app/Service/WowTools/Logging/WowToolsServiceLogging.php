<?php

namespace App\Service\WowTools\Logging;

use App\Logging\StructuredLogging;

class WowToolsServiceLogging extends StructuredLogging implements WowToolsServiceLoggingInterface
{
    /**
     * @param int $npcId
     * @return void
     */
    public function getDisplayIdRequestStart(int $npcId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $error
     * @return void
     */
    public function getDisplayIdRequestError(string $error): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @param int $displayInfoId
     * @return void
     */
    public function getDisplayIdRequestResult(int $displayInfoId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function getDisplayIdRequestResultUnableFindCreateDisplayInfoID(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function getDisplayIdRequestEnd(): void
    {
        $this->end(__METHOD__);
    }
}
