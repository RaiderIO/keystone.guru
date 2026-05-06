<?php

namespace App\Service\RaiderIO\Logging;

use App\Logging\RollbarStructuredLogging;

class RaiderIOApiServiceLogging extends RollbarStructuredLogging implements RaiderIOApiServiceLoggingInterface
{
    public function getHeatmapDataStart(string $url): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getHeatmapDataInvalidResponse(string $dungeonName, string $url, string $response): void
    {
        // @TODO temporarily disable logging of invalid responses, it's spamming the logs
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function getHeatmapDataEnd(): void
    {
        $this->end(__METHOD__);
    }
}
