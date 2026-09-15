<?php

namespace App\Service\WagoTools\Logging;

interface WagoToolsServiceLoggingInterface
{
    public function getLatestBuildRequestFailed(string $product): void;

    public function getLatestBuildUnknownProduct(string $product): void;

    public function getLatestBuildInvalidResponse(string $product): void;

    public function getTableCsvPathCacheHit(string $table, string $build): void;

    public function downloadTableStart(string $table, string $build): void;

    public function downloadTableEnd(): void;

    public function downloadTableFailed(string $url, int $httpCode, string $curlError): void;

    public function getIconFileNamesByFileDataIdsStart(int $fileDataIdCount): void;

    public function getIconFileNamesByFileDataIdsNotFound(int $fileDataId): void;

    public function getIconFileNamesByFileDataIdsEnd(): void;
}
