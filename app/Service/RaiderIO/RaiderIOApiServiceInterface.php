<?php

namespace App\Service\RaiderIO;

use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\RaiderIO\Dtos\HeatmapDataResponse\HeatmapDataResponse;

interface RaiderIOApiServiceInterface
{
    public function getHeatmapData(HeatmapDataFilter $heatmapDataFilter): HeatmapDataResponse;
}
