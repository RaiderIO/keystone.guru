<?php

namespace App\Service\RaiderIO;

use App\Service\RaiderIO\Dtos\CombatLogDownloadResponse;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\RaiderIO\Dtos\HeatmapDataResponse\HeatmapDataResponse;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsResponse;
use App\Service\RaiderIO\Exceptions\InvalidApiResponseException;

interface RaiderIOApiServiceInterface
{
    /**
     * @throws InvalidApiResponseException
     */
    public function getHeatmapData(HeatmapDataFilter $heatmapDataFilter): HeatmapDataResponse;

    public function searchAdvancedRuns(SearchAdvancedRunsFilter $filter): SearchAdvancedRunsResponse;

    /**
     * Returns null if the download URL is not configured or the API returns an invalid response.
     */
    public function getCombatLogForRun(int $runId): ?CombatLogDownloadResponse;
}
