<?php

namespace App\Service\CombatLog\Exceptions;

use App\Service\Traits\Dtos\CurlDownloadResult;
use RuntimeException;

/**
 * Thrown when a presigned combat log segment download URL fails (expired, denied, or otherwise
 * non-2xx). Extends {@see RuntimeException} so it is still caught and retried by
 * {@see \App\Jobs\CombatLog\ProcessCombatLogSegments::handle()} like any other transient infra
 * blip, but as its own class so `bootstrap/app.php` can report it at warning rather than error
 * level: one run's segment failing to download costs nothing, the run is simply skipped and its
 * parsing budget given back.
 */
class CombatLogSegmentDownloadFailedException extends RuntimeException
{
    public static function forSegment(int $segmentId, int $runId, CurlDownloadResult $downloadResult): self
    {
        return new self(sprintf(
            'Failed to download segment %d for run %d (http=%d, curl=%d)',
            $segmentId,
            $runId,
            $downloadResult->httpCode,
            $downloadResult->errorNumber,
        ));
    }
}
