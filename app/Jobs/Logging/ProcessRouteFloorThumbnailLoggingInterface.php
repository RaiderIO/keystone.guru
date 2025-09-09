<?php

namespace App\Jobs\Logging;

interface ProcessRouteFloorThumbnailLoggingInterface
{

    public function handleStart(
        string $publicKey,
        int    $dungeonRouteId,
        int    $mappingVersionId,
        int    $floorIndex,
        int    $attempts
    ): void;

    public function handleCreateThumbnailError(): void;

    public function handleThumbnailAlreadyUpToDate(): void;

    public function handleMaxAttemptsReached(): void;

    public function handleEnd(bool $result): void;
}
