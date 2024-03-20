<?php

namespace App\Service\StructuredLogging\Logging;

interface StructuredLoggingServiceLoggingInterface
{
    public function debugLog(): void;

    public function infoLog(): void;

    public function noticeLog(): void;

    public function warningLog(): void;

    public function errorLog(): void;

    public function criticalLog(): void;

    public function alertLog(): void;

    public function emergencyLog(): void;
}
