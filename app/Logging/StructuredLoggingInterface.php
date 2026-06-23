<?php

namespace App\Logging;

interface StructuredLoggingInterface
{
    /**
     * @param array<string, mixed> ...$context
     */
    public function addContext(string $key, array ...$context): void;

    public function removeContext(string $key): void;
}
