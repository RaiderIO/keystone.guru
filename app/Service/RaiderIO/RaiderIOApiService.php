<?php

namespace App\Service\RaiderIO;

use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\RaiderIO\Dtos\HeatmapDataResponse\HeatmapDataResponse;
use App\Service\RaiderIO\Logging\RaiderIOApiServiceLoggingInterface;
use App\Service\Traits\Curl;
use Exception;
use Str;

class RaiderIOApiService implements RaiderIOApiServiceInterface
{
    use Curl;

    public function __construct(
        private readonly RaiderIOApiServiceLoggingInterface $log
    ) {
    }

    public function getHeatmapData(HeatmapDataFilter $heatmapDataFilter): HeatmapDataResponse
    {
        $parameters = [];
        foreach ($heatmapDataFilter->toArray() as $key => $value) {
            $key = match ($key) {
                'event_type' => 'type',
                default => $key
            };
            $parameters[] = sprintf('%s=%s', Str::camel($key), $value);
        }
        $url = sprintf(
            '%s?%s',
            'https://raider.io/api/v1/live-tracking/heatmaps/grid',
            implode('&', $parameters)
        );

        try {
            $this->log->getHeatmapDataStart($url);

            $response = $this->curlGet($url);

            $this->log->getHeatmapDataResponse($response);

            $json = json_decode($response, true);

            if (!is_array($json)) {
                throw new Exception(sprintf('Invalid response from RaiderIO API %s', $response));
            }

            return HeatmapDataResponse::fromArray($json);
        } finally {
            $this->log->getHeatmapDataEnd();
        }
    }

}
