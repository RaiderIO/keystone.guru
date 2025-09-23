<?php

namespace App\Jobs\Logging;

use App\Logging\StructuredLogging;

class ProcessRouteFloorThumbnailLogging extends StructuredLogging implements ProcessRouteFloorThumbnailLoggingInterface
{
    public function handleStart(
        string $publicKey,
        int    $dungeonRouteId,
        int    $mappingVersionId,
        int    $floorIndex,
        int    $attempts,
    ): void {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function handleCreateThumbnailError(): void
    {
        $this->warning(__METHOD__);
    }

    public function handleThumbnailAlreadyUpToDate(): void
    {
        $this->info(__METHOD__);
    }

    public function handleMaxAttemptsReached(): void
    {
        $this->warning(__METHOD__);
    }

    public function handleEnd(bool $result): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
