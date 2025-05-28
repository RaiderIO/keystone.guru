<?php

namespace App\Service\DungeonRoute\Logging;

use Exception;
use Throwable;

interface ThumbnailServiceLoggingInterface
{

    public function createThumbnailStart(string $publicKey, int $floorIndex, int $attempts): void;

    public function createThumbnailEnd(): void;

    public function createThumbnailCustomStart(string $publicKey, int $floorIndex, int $attempts, ?int $viewportWidth, ?int $viewportHeight, ?int $imageWidth, ?int $imageHeight, ?int $zoomLevel, ?int $quality): void;

    public function createThumbnailCustomEnd(): void;

    public function doCreateThumbnailStart(string $publicKey, int $floorIndex, string $targetFolder, ?int $viewportWidth, ?int $viewportHeight, ?int $imageWidth, ?int $imageHeight, ?int $zoomLevel, ?int $quality): void;

    public function doCreateThumbnailMaintenanceMode(): void;

    public function doCreateThumbnailProcessStart(string $commandLine): void;

    public function doCreateThumbnailFileNotFoundDidPuppeteerDownloadChromium(string $tmpFile): void;

    public function doCreateThumbnailRescale(string $tmpFile, string $target): void;

    public function doCreateThumbnailRemovedOldPngFile(): void;

    public function doCreateThumbnailSuccess(string $target, bool $fileExists): void;

    public function doCreateThumbnailException(Throwable|Exception $e): void;

    public function doCreateThumbnailRemovedTmpFileSuccess(): void;

    public function doCreateThumbnailRemovedTmpFileFailure(): void;

    public function doCreateThumbnailError(string $errors): void;

    public function queueThumbnailRefreshMappingVersionNull(string $publicKey): void;

    public function queueThumbnailRefreshDispatchedJob(string $publicKey, int $index, bool $force): void;

    public function doCreateThumbnailEnd(): void;

    public function copyThumbnailsError(string $sourcePublicKey, string $targetPublicKey, int $id, Exception $exception): void;
}
