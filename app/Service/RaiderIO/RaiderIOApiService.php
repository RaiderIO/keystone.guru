<?php

namespace App\Service\RaiderIO;

use App\Logic\Utils\Stopwatch;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\RaiderIO\Dtos\HeatmapDataResponse\HeatmapDataResponse;
use App\Service\RaiderIO\Dtos\RaiderIOHeatmapGridResponse;
use App\Service\RaiderIO\Logging\RaiderIOApiServiceLoggingInterface;
use App\Service\Season\SeasonServiceInterface;
use App\Service\Traits\Curl;
use Exception;
use Str;

class RaiderIOApiService implements RaiderIOApiServiceInterface
{
    private const BASE_URL = 'https://raider.io/api/v1';

    use Curl;

    public function __construct(
        private readonly CoordinatesServiceInterface        $coordinatesService,
        private readonly SeasonServiceInterface             $seasonService,
        private readonly CombatLogEventServiceInterface     $combatLogEventService,
        private readonly RaiderIOApiServiceLoggingInterface $log
    ) {
    }

    public function getHeatmapData(HeatmapDataFilter $heatmapDataFilter): HeatmapDataResponse
    {
        $mostRecentSeason = $this->seasonService->getMostRecentSeasonForDungeon($heatmapDataFilter->getDungeon());
        $parameters       = [
            sprintf(
                'season=season-%s-%s',
                $mostRecentSeason->expansion->shortname,
                $mostRecentSeason->index
            ),
        ];

        foreach ($heatmapDataFilter->toArray($mostRecentSeason) as $key => $value) {
            $parameters[] = sprintf('%s=%s', Str::camel($key), $value);
        }

        /*
         * Exclude data points that fall below this factor of the max amount of points in the grid.
         * Say that the top hot spot was 10000 entries, then in order to be included in this heatmap, a data point
         * must have at least 10000 * factor entries in order to be returned. This cuts down on the amount of data
         * being sent by the server to KSG, and KSG to the browser.
         */

        $minRequiredSampleFactor = config('keystoneguru.heatmap.api.min_required_sample_factor_default');
        if ($heatmapDataFilter->getMinSamplesRequired() === null && $minRequiredSampleFactor !== null) {
            $parameters[] = sprintf('minRequiredSampleFactor=%s', $minRequiredSampleFactor);
        }

        $floorsAsArray = config('keystoneguru.heatmap.api.floors_as_array');
        if ($floorsAsArray === true) {
            $parameters[] = 'floorsAsArray=true';
        }

        $url = sprintf(
            '%s?%s',
            sprintf('%s/live-tracking/heatmaps/grid', self::BASE_URL),
            implode('&', $parameters)
        );

        try {
            $this->log->getHeatmapDataStart($url);

            $response = $this->curlGet($url);

            $json = json_decode($response, true);

            if (!is_array($json) || !isset($json['gridsByFloor'], $json['numRuns'])) {
                $this->log->getHeatmapDataInvalidResponse($response);

                throw new Exception(sprintf('Invalid response from RaiderIO API %s', $response));
            }

            return HeatmapDataResponse::fromArray(
                (new RaiderIOHeatmapGridResponse(
                    $this->coordinatesService,
                    CombatLogEventFilter::fromHeatmapDataFilter($this->seasonService, $heatmapDataFilter),
                    $json['gridsByFloor'],
                    $json['numRuns'],
                    $json['maxSamplesInGrid'],
                    $url,
                    $floorsAsArray
                ))->toArray()
            );
        } finally {
            $this->log->getHeatmapDataEnd();
        }
    }

}
