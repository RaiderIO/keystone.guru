<?php

namespace App\Service\Wago\Logging;

interface WagoToolsServiceLoggingInterface
{
    public function getIconFileNamesByFileDataIdsStart(int $fileDataIdCount): void;

    public function getIconFileNamesByFileDataIdsDownloadFailed(string $url): void;

    public function getIconFileNamesByFileDataIdsUnableToOpenFile(string $filePath): void;

    public function getIconFileNamesByFileDataIdsNotFound(int $fileDataId): void;

    public function getIconFileNamesByFileDataIdsEnd(): void;
}
