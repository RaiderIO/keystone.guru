<?php

namespace App\Service\CombatLog\Exceptions;

use Exception;
use Throwable;

/**
 * Thrown when a combat log line cannot be parsed. Carries the offending line number and raw line so the
 * failure can be persisted and re-inspected later, while wrapping the original exception as the cause.
 *
 * Intentionally extends {@see Exception} (not {@see \RuntimeException}) so callers that treat
 * RuntimeExceptions as retryable download failures do not mistake a parse error for one.
 */
class CombatLogParseException extends Exception
{
    public function __construct(
        public readonly int    $lineNumber,
        public readonly string $rawLine,
        string                 $message,
        ?Throwable             $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * The class name of the original exception that caused this parse failure, e.g. `InvalidArgumentException`.
     */
    public function getOriginalExceptionClass(): string
    {
        return $this->getPrevious() !== null ? $this->getPrevious()::class : self::class;
    }
}
