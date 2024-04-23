<?php

namespace App\Service\StructuredLogging;

use App\Service\StructuredLogging\Logging\StructuredLoggingServiceLoggingInterface;

class StructuredLoggingService implements StructuredLoggingServiceInterface
{

    public function __construct(
        private readonly StructuredLoggingServiceLoggingInterface $log
    ) {
    }

    public function all(): void
    {
        $this->debug();
        $this->info();
        $this->notice();
        $this->warning();
        $this->error();
        $this->critical();
        $this->alert();
        $this->emergency();
    }

    public function debug(): void
    {
        $this->log->debugLog();
    }

    public function info(): void
    {
        $this->log->infoLog();
    }

    public function notice(): void
    {
        $this->log->noticeLog();
    }

    public function warning(): void
    {
        $this->log->warningLog();
    }

    public function error(): void
    {
        $this->log->errorLog();
    }

    public function critical(): void
    {
        $this->log->criticalLog();
    }

    public function alert(): void
    {
        $this->log->alertLog();
    }

    public function emergency(): void
    {
        $this->log->emergencyLog();
    }

}
