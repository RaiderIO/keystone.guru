<?php

namespace App\Service\StructuredLogging;

interface StructuredLoggingServiceInterface
{
    public function all(): void;

    public function debug(): void;

    public function info(): void;

    public function notice(): void;

    public function warning(): void;

    public function error(): void;

    public function critical(): void;

    public function alert(): void;

    public function emergency(): void;
}
