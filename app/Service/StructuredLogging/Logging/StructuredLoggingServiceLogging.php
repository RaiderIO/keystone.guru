<?php

namespace App\Service\StructuredLogging\Logging;

use App\Logging\RollbarStructuredLogging;

class StructuredLoggingServiceLogging extends RollbarStructuredLogging implements StructuredLoggingServiceLoggingInterface
{
    public function debugLog(): void
    {
        $this->debug(__FUNCTION__);
    }

    public function infoLog(): void
    {
        $this->info(__FUNCTION__);
    }

    public function noticeLog(): void
    {
        $this->notice(__FUNCTION__);
    }

    public function warningLog(): void
    {
        $this->warning(__FUNCTION__);
    }

    public function errorLog(): void
    {
        $this->error(__FUNCTION__);
    }

    public function criticalLog(): void
    {
        $this->critical(__FUNCTION__);
    }

    public function alertLog(): void
    {
        $this->alert(__FUNCTION__);
    }

    public function emergencyLog(): void
    {
        $this->emergency(__FUNCTION__);
    }
}
