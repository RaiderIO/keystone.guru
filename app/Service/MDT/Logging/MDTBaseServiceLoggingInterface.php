<?php

namespace App\Service\MDT\Logging;

use App\Logging\StructuredLoggingInterface;

interface MDTBaseServiceLoggingInterface extends StructuredLoggingInterface
{
    public function decodeFailed(string $string, string $exceptionClass, string $message): void;

    public function decodeInvalidStringFailed(string $string, string $exceptionClass, string $message): void;
}
