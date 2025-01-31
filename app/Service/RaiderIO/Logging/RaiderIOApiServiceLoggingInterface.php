<?php

namespace App\Service\RaiderIO\Logging;

interface RaiderIOApiServiceLoggingInterface
{
    public function getHeatmapDataStart(string $url): void;

    public function getHeatmapDataInvalidResponse(string $response): void;

    public function getHeatmapDataEnd(): void;
}
