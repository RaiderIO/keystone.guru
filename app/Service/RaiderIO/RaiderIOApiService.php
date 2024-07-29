<?php

namespace App\Service\RaiderIO;

use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\RaiderIO\Dtos\HeatmapDataResponse\HeatmapDataResponse;
use App\Service\Traits\Curl;
use Exception;

class RaiderIOApiService implements RaiderIOApiServiceInterface
{
    use Curl;

    public function getHeatmapData(HeatmapDataFilter $heatmapDataFilter): HeatmapDataResponse
    {
        // @TODO This endpoint does not exist, it's just a dummy for now
        $url      = 'https://raider.io/api/v1/keystoneguru/heatmap/data?region=eu&locale=en';
        $response = $this->curlPost($url, $heatmapDataFilter->toArray());

        $json = json_decode($response, true);

        if (!is_array($json)) {
            // @TODO Add structured logging
            throw new Exception(sprintf('Invalid response from RaiderIO API %s', $response));
        }

        return HeatmapDataResponse::fromArray($json);
    }

}
