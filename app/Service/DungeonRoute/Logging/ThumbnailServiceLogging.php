<?php

namespace App\Service\DungeonRoute\Logging;

use App\Logging\StructuredLogging;
use Exception;
use Throwable;

class ThumbnailServiceLogging extends StructuredLogging implements ThumbnailServiceLoggingInterface
{
    public function createThumbnailStart(string $publicKey, int $floorIndex, int $attempts): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function createThumbnailEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function createThumbnailCustomStart(string $publicKey, int $floorIndex, int $attempts, ?int $viewportWidth, ?int $viewportHeight, ?int $imageWidth, ?int $imageHeight, ?int $zoomLevel, ?int $quality): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function createThumbnailCustomEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function doCreateThumbnailStart(string $publicKey, int $floorIndex, string $targetFolder, ?int $viewportWidth, ?int $viewportHeight, ?int $imageWidth, ?int $imageHeight, ?int $zoomLevel, ?int $quality): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function doCreateThumbnailMaintenanceMode(): void
    {
        $this->info(__METHOD__);
    }

    public function doCreateThumbnailProcessStart(string $commandLine): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function doCreateThumbnailFileNotFoundDidPuppeteerDownloadChromium(string $tmpFile): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function doCreateThumbnailRescale(string $tmpFile, string $target): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function doCreateThumbnailRemovedOldPngFile(): void
    {
        $this->debug(__METHOD__);
    }

    public function doCreateThumbnailSuccess(string $target, bool $fileExists): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function doCreateThumbnailException(Throwable|Exception $e): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function doCreateThumbnailRemovedTmpFileSuccess(): void
    {
        $this->debug(__METHOD__);
    }

    public function doCreateThumbnailRemovedTmpFileFailure(): void
    {
        $this->warning(__METHOD__);
    }

    public function doCreateThumbnailError(string $errors): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function queueThumbnailRefreshMappingVersionNull(string $publicKey): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function queueThumbnailRefreshAlreadyQueued(string $publicKey): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }


    public function doCreateThumbnailEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function copyThumbnailsError(string $sourcePublicKey, string $targetPublicKey, int $id, Exception $exception): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

}
