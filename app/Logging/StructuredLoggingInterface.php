<?php

namespace App\Logging;

interface StructuredLoggingInterface
{
    public function addContext(string $key, ...$context): void;

    public function removeContext(string $key): void;
}
