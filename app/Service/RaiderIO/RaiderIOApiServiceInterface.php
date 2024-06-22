<?php

namespace App\Service\RaiderIO;

use App\Service\RaiderIO\Dtos\HeatmapDataResponse\HeatmapDataResponse;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;

interface RaiderIOApiServiceInterface
{
    public function getHeatmapData(HeatmapDataFilter $heatmapDataFilter): HeatmapDataResponse;
}
