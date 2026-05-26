<?php

namespace App\Service\RaiderIO\Dtos;

class CombatLogDownloadResponse
{
    /**
     * @param bool $isFile True when s3Path points to a single file rather than a folder of files.
     *                     Used by FetchCombatLogRunFanout to decide whether to dispatch
     *                     ProcessCombatLogPart directly (local/mock) or ProcessCombatLogFanout (production).
     */
    public function __construct(
        public readonly string $diskName,
        public readonly string $s3Bucket,
        public readonly string $s3Path,
        public readonly int    $combatLogVersion,
        public readonly bool   $isFile,
    ) {
    }
}
