<?php

namespace App\Service\RaiderIO;

use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\RaiderIO\Dtos\CombatLogDownloadResponse;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\RaiderIO\Dtos\HeatmapDataResponse\HeatmapDataResponse;
use App\Service\RaiderIO\Dtos\RaiderIOHeatmapGridResponse;
use App\Service\RaiderIO\Dtos\SearchAdvancedRun;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsResponse;
use App\Service\RaiderIO\Exceptions\InvalidApiResponseException;
use App\Service\RaiderIO\Logging\RaiderIOApiServiceLoggingInterface;
use App\Service\Season\SeasonServiceInterface;
use App\Service\Traits\Curl;
use Str;

class RaiderIOApiService implements RaiderIOApiServiceInterface
{
    private const string BASE_URL = 'https://raider.io/api/v1';

    private const string SEARCH_ADVANCED_URL = 'https://raider.io/api/search-advanced';

    private const array EXPANSION_SHORTNAME_OVERRIDE = [
        'midnight' => 'mn',
    ];

    use Curl;

    public function __construct(
        private readonly CoordinatesServiceInterface        $coordinatesService,
        private readonly SeasonServiceInterface             $seasonService,
        private readonly CombatLogEventServiceInterface     $combatLogEventService,
        private readonly RaiderIOApiServiceLoggingInterface $log,
    ) {
    }

    public function getHeatmapData(HeatmapDataFilter $heatmapDataFilter): HeatmapDataResponse
    {
        // Ensure a season is set, even if it wasn't passed
        if ($heatmapDataFilter->getSeason() === null) {
            $mostRecentSeason = $this->seasonService->getMostRecentSeasonForDungeon($heatmapDataFilter->getDungeon());
            if ($mostRecentSeason !== null) {
                $heatmapDataFilter->setSeason($this->buildSeasonString(
                    $mostRecentSeason->expansion->shortname,
                    $mostRecentSeason->index,
                ));
            }
        }

        $parameters = [];
        foreach ($heatmapDataFilter->toArray() as $key => $value) {
            $parameters[] = sprintf('%s=%s', Str::camel($key), $value);
        }

        $url = sprintf(
            '%s?%s',
            sprintf('%s/live-tracking/heatmaps/grid', self::BASE_URL),
            implode('&', $parameters),
        );

        try {
            $this->log->getHeatmapDataStart($url);

            $response = $this->curlGet($url);

            $json = json_decode($response, true);

            if (!is_array($json) || !isset($json['gridsByFloor'], $json['numRuns'])) {
                $this->log->getHeatmapDataInvalidResponse(
                    __($heatmapDataFilter->getDungeon()->name, [], 'en_US'),
                    $url,
                    $response,
                );

                throw new InvalidApiResponseException('Invalid response from Raider.IO API', $url, $response);
            }

            return HeatmapDataResponse::fromArray(
                new RaiderIOHeatmapGridResponse(
                    $this->coordinatesService,
                    CombatLogEventFilter::fromHeatmapDataFilter($this->seasonService, $heatmapDataFilter),
                    $json['gridsByFloor'],
                    $json['numRuns'],
                    $json['maxSamplesInGrid'],
                    $url,
                    $heatmapDataFilter->getFloorsAsArray(),
                )->toArray(),
            );
        } finally {
            $this->log->getHeatmapDataEnd();
        }
    }

    public function searchAdvancedRuns(SearchAdvancedRunsFilter $filter): SearchAdvancedRunsResponse
    {
        $params = [
            'type'                => 'mythic_plus_runs',
            'hasAutoRoute[0][eq]' => 1,
            'season[0][eq]'       => $filter->season,
            'mythicLevel[0][gte]' => $filter->mythicLevelMin,
            'numChests[0][eq]'    => 1,
            'numChests[1][eq]'    => 2,
            'numChests[2][eq]'    => 3,
            'completedAt[0][gte]' => $filter->completedAtFrom->toDateString(),
            'timezone'            => 'UTC',
            'sort[hasAutoRoute]'  => 'desc',
            'limit'               => $filter->limit,
            'offset'              => $filter->offset,
        ];

        if ($filter->dungeonZoneId !== null) {
            $params['dungeonZoneId[0][eq]'] = $filter->dungeonZoneId;
        }

        if ($filter->completedAtTo !== null) {
            $params['completedAt[0][lte]'] = $filter->completedAtTo->toDateString();
        }

        foreach ($filter->specBlizzardIds as $index => $specId) {
            $params[sprintf('memberSpecIds[%d][eq]', $index)] = $specId;
        }

        $url = sprintf('%s?%s', self::SEARCH_ADVANCED_URL, http_build_query($params));

        try {
            $this->log->searchAdvancedRunsStart($url);

            $response = $this->curlGet($url);
            $json     = json_decode($response, true);

            if (!is_array($json) || !isset($json['matches']) || !is_array($json['matches'])) {
                $this->log->searchAdvancedRunsInvalidResponse($url, $response);

                return new SearchAdvancedRunsResponse([], null);
            }

            $runs = [];
            foreach ($json['matches'] as $match) {
                if (!isset($match['data'])) {
                    continue;
                }
                $runs[] = SearchAdvancedRun::fromArray($match['data']);
            }

            $total = isset($json['total']['value']) ? (int)$json['total']['value'] : null;

            return new SearchAdvancedRunsResponse($runs, $total);
        } finally {
            $this->log->searchAdvancedRunsEnd(count($runs ?? []));
        }
    }

    public function getCombatLogForRun(int $runId): ?CombatLogDownloadResponse
    {
        $this->log->getCombatLogForRunStart($runId);

        $downloadUrl = config('keystoneguru.raider_io.combat_log_polling.download_url');

        if (empty($downloadUrl)) {
            $this->log->getCombatLogForRunNotConfigured();

            return null;
        }

        $url = sprintf('%s/%d', rtrim($downloadUrl, '/'), $runId);

        try {
            $response = $this->curlGet($url);
            $json     = json_decode($response, true);

            if (!is_array($json) || !isset($json['s3_bucket'], $json['s3_path'], $json['combat_log_version'])) {
                $this->log->getCombatLogForRunInvalidResponse($runId, $url, $response);

                return null;
            }

            return new CombatLogDownloadResponse(
                diskName:        's3_combat_logs',
                s3Bucket:        (string)$json['s3_bucket'],
                s3Path:          (string)$json['s3_path'],
                combatLogVersion: (int)$json['combat_log_version'],
                isFile:          false,
            );
        } finally {
            $this->log->getCombatLogForRunEnd($runId);
        }
    }

    private function buildSeasonString(string $expansionShortname, int $seasonIndex): string
    {
        return sprintf(
            'season-%s-%d',
            self::EXPANSION_SHORTNAME_OVERRIDE[$expansionShortname] ?? $expansionShortname,
            $seasonIndex,
        );
    }
}
