<?php

namespace App\Service\WagoTools\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class WagoToolsServiceLogging extends StructuredLogging implements WagoToolsServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function getLatestBuildRequestFailed(string $product): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getLatestBuildUnknownProduct(string $product): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getLatestBuildInvalidResponse(string $product): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getTableCsvPathCacheHit(string $table, string $build): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function downloadTableStart(string $table, string $build): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function downloadTableEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function downloadTableFailed(string $url, int $httpCode, string $curlError): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getIconFileNamesByFileDataIdsStart(int $fileDataIdCount): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getIconFileNamesByFileDataIdsNotFound(int $fileDataId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function getIconFileNamesByFileDataIdsEnd(): void
    {
        $this->end(__METHOD__);
    }
}
