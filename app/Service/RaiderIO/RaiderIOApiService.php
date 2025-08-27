<?php

namespace App\Service\RaiderIO;

use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\RaiderIO\Dtos\HeatmapDataResponse\HeatmapDataResponse;
use App\Service\RaiderIO\Dtos\RaiderIOHeatmapGridResponse;
use App\Service\RaiderIO\Exceptions\InvalidApiResponseException;
use App\Service\RaiderIO\Logging\RaiderIOApiServiceLoggingInterface;
use App\Service\Season\SeasonServiceInterface;
use App\Service\Traits\Curl;
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
                $this->log->getHeatmapDataInvalidResponse(
                    __($heatmapDataFilter->getDungeon()->name, [], 'en_US'),
                    $url,
                    $response
                );

                throw new InvalidApiResponseException('Invalid response from Raider.IO API');
            }

            return HeatmapDataResponse::fromArray(
                (new RaiderIOHeatmapGridResponse(
                    $this->coordinatesService,
                    CombatLogEventFilter::fromHeatmapDataFilter($this->seasonService, $heatmapDataFilter),
                    $json['gridsByFloor'],
                    $json['numRuns'],
                    $json['maxSamplesInGrid'],
                    $url,
                    $heatmapDataFilter->getFloorsAsArray(),
                ))->toArray()
            );
        } finally {
            $this->log->getHeatmapDataEnd();
        }
    }

}
