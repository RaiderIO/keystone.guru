<?php

namespace App\Logging;

/**
 * Base of every interface whose log events are declared with #[LogEvent] and typed with @method. Declaring
 * __call lets a PHPUnit mock of such an interface record its calls.
 */
interface EventStructuredLoggingInterface extends StructuredLoggingInterface
{
    /**
     * @param array<int|string, mixed> $arguments
     */
    public function __call(string $name, array $arguments): void;
}
