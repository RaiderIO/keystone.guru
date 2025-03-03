<?php

namespace App\Service\RaiderIO;

use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\RaiderIO\Dtos\HeatmapDataResponse\HeatmapDataResponse;
use App\Service\RaiderIO\Exceptions\InvalidApiResponseException;

interface RaiderIOApiServiceInterface
{
    /**
     * @throws InvalidApiResponseException
     */
    public function getHeatmapData(HeatmapDataFilter $heatmapDataFilter): HeatmapDataResponse;
}
