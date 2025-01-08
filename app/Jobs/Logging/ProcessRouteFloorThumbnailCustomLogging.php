<?php

namespace App\Jobs\Logging;

use App\Logging\StructuredLogging;

class ProcessRouteFloorThumbnailCustomLogging extends StructuredLogging implements ProcessRouteFloorThumbnailCustomLoggingInterface
{
    public function handleStart(string $publicKey, int $floorIndex, int $id, int $attempts, ?int $viewportWidth, ?int $viewportHeight, ?int $imageWidth, ?int $imageHeight, ?float $zoomLevel, ?int $quality): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function handleCreateCustomThumbnailError(): void
    {
        $this->error(__METHOD__);
    }

    public function handleFinishedProcessing(): void
    {
        $this->debug(__METHOD__);
    }

    public function handleMaxAttemptsReached(): void
    {
        $this->warning(__METHOD__);
    }

    public function handleEnd(): void
    {
        $this->end(__METHOD__);
    }
}
