<?php

namespace App\Jobs\Logging;

interface ProcessRouteFloorThumbnailCustomLoggingInterface
{
    public function handleStart(
        string $publicKey,
        int    $floorIndex,
        int    $id,
        int    $attempts,
        ?int   $viewportWidth,
        ?int   $viewportHeight,
        ?int   $imageWidth,
        ?int   $imageHeight,
        ?float $zoomLevel,
        ?int   $quality,
    ): void;

    public function handleCreateCustomThumbnailError(): void;

    public function handleFinishedProcessing(): void;

    public function handleMaxAttemptsReached(): void;

    public function handleEnd(): void;
}
