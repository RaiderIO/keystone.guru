<?php

namespace App\Service\RaiderIO\Logging;

use App\Logging\RollbarStructuredLogging;

class RaiderIOApiServiceLogging extends RollbarStructuredLogging implements RaiderIOApiServiceLoggingInterface
{
    public function getHeatmapDataStart(string $url): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getHeatmapDataResponse(string $response): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function getHeatmapDataInvalidResponse(string $response): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getHeatmapDataEnd(): void
    {
        $this->end(__METHOD__);
    }

}
