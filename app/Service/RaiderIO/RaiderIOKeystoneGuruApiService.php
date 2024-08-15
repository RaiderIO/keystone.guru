<?php

namespace App\Service\RaiderIO;

use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\RaiderIO\Dtos\HeatmapDataResponse\HeatmapDataResponse;
use App\Service\Season\SeasonServiceInterface;
use App\Service\Traits\Curl;

/**
 * This service mocks the RaiderIO API service and returns data from Keystone.guru instead for the interim
 */
class RaiderIOKeystoneGuruApiService implements RaiderIOApiServiceInterface
{
    use Curl;

    public function __construct(
        private readonly SeasonServiceInterface         $seasonService,
        private readonly CombatLogEventServiceInterface $combatLogEventService
    ) {

    }

    public function getHeatmapData(HeatmapDataFilter $heatmapDataFilter): HeatmapDataResponse
    {
        return HeatmapDataResponse::fromArray(
            $this->combatLogEventService->getGridAggregation(
                CombatLogEventFilter::fromHeatmapDataFilter($this->seasonService, $heatmapDataFilter)
            )->toArray()
        );
    }

}
