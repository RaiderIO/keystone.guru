<?php

namespace App\Exceptions\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;
use Throwable;

class HandlerLogging extends StructuredLogging implements HandlerLoggingInterface
{
    use InteractsWithRollbar;

    public function tooManyRequests(
        string    $ip,
        string    $url,
        ?int      $userId,
        ?string   $username,
        Throwable $throwable,
    ): void {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @param array<string, mixed>|null $body
     * @param bool                      $reportedByErrorTracker Ends up in the log context, where a channel that also
     *                                                          receives the exception natively filters this record
     *                                                          out again - see SkipsExceptionMirrorsHandler.
     */
    public function uncaughtException(
        string  $ip,
        string  $url,
        ?int    $userId,
        ?string $username,
        ?array  $body,
        string  $exceptionClass,
        string  $message,
        bool    $reportedByErrorTracker,
    ): void {
        unset($body['lines']);
        $this->error(__METHOD__, get_defined_vars());
    }
}
