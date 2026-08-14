<?php

namespace App\Service\Wago\Logging;

use App\Logging\StructuredLogging;

class WagoToolsServiceLogging extends StructuredLogging implements WagoToolsServiceLoggingInterface
{
    public function getIconFileNamesByFileDataIdsStart(int $fileDataIdCount): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getIconFileNamesByFileDataIdsDownloadFailed(string $url): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getIconFileNamesByFileDataIdsUnableToOpenFile(string $filePath): void
    {
        $this->error(__METHOD__, get_defined_vars());
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
