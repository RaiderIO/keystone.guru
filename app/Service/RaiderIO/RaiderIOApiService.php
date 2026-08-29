<?php

namespace App\Service\RaiderIO;

use App\Models\Season;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\RaiderIO\Dtos\CombatLogSegment;
use App\Service\RaiderIO\Dtos\CombatLogSegmentsResponse;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\RaiderIO\Dtos\HeatmapDataResponse\HeatmapDataResponse;
use App\Service\RaiderIO\Dtos\RaiderIOHeatmapGridResponse;
use App\Service\RaiderIO\Dtos\SearchAdvancedRun;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsResponse;
use App\Service\RaiderIO\Enums\RaiderIOFaction;
use App\Service\RaiderIO\Exceptions\InvalidApiResponseException;
use App\Service\RaiderIO\Logging\RaiderIOApiServiceLoggingInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use App\Service\Traits\Curl;
use Str;
use Teapot\StatusCode\Http;

class RaiderIOApiService implements RaiderIOApiServiceInterface
{
    private const string BASE_URL = 'https://raider.io/api/v1';

    private const string SEARCH_ADVANCED_URL = 'https://raider.io/api/search-advanced';

    private const string SEGMENTS_URL = 'https://raider.io/api/v1/live-tracking/mythic-plus/combatlog-segments';

    private const array EXPANSION_SHORTNAME_OVERRIDE = [
        'midnight' => 'mn',
    ];

    use Curl;

    public function __construct(
        private readonly CoordinatesServiceInterface        $coordinatesService,
        private readonly SeasonServiceInterface             $seasonService,
        private readonly SeasonAffixGroupServiceInterface   $seasonAffixGroupService,
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
                    CombatLogEventFilter::fromHeatmapDataFilter($this->seasonService, $this->seasonAffixGroupService, $heatmapDataFilter),
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
        $completedAt = ['gte' => $filter->completedAtFrom->toDateString()];

        if ($filter->completedAtTo !== null) {
            $completedAt['lte'] = $filter->completedAtTo->toDateString();
        }

        $dungeonZoneId = $filter->dungeon?->zone_id;
        $faction       = RaiderIOFaction::fromFaction($filter->faction);
        $memberSpecIds = $filter->specs
            ->pluck('specialization_id')
            ->map(fn($specId) => ['eq' => (int)$specId])
            ->values()
            ->toArray();

        $mythicLevel = ['gte' => $filter->mythicLevelMin];

        if ($filter->mythicLevelMax !== null) {
            $mythicLevel['lte'] = $filter->mythicLevelMax;
        }

        $params = array_filter([
            'type'         => 'mythic_plus_runs',
            'hasAutoRoute' => [0 => ['eq' => 1]],
            'season'       => [0 => ['eq' => $this->buildSeasonString($filter->season->expansion->shortname, $filter->season->index)]],
            'mythicLevel'  => [0 => $mythicLevel],
            'numChests'    => [
                0 => ['eq' => 1],
                1 => ['eq' => 2],
                2 => ['eq' => 3],
            ],
            'completedAt' => [0 => $completedAt],
            'timezone'    => 'UTC',
            // Sorting on hasAutoRoute is a no-op - it is already filtered to 1 - which leaves the
            // ordering stable across identical calls. Repeated polls of the same narrow filter then
            // return the exact same page, and every run on it has already been parsed. Ordering by
            // recency instead means each poll leads with the runs that appeared since the last one.
            'sort'          => ['completedAt' => 'desc'],
            'limit'         => $filter->limit,
            'offset'        => $filter->offset,
            'dungeonZoneId' => $dungeonZoneId !== null ? [0 => ['eq' => $dungeonZoneId]] : null,
            'memberSpecIds' => !empty($memberSpecIds) ? $memberSpecIds : null,
            // A run only carries a faction when every member shares one - a cross faction group has
            // none at all, so this filter always excludes those along with the other faction.
            'faction' => $faction !== null ? [0 => ['eq' => $faction->value]] : null,
        ]);

        $url = sprintf('%s?%s', self::SEARCH_ADVANCED_URL, http_build_query($params));

        try {
            $this->log->searchAdvancedRunsStart($url);

            $response = $this->curlGet($url);
            $json     = json_decode($response, true);

            if (!is_array($json) || !isset($json['matches']) || !is_array($json['matches'])) {
                $this->log->searchAdvancedRunsInvalidResponse($url, $response);

                // A null total is what tells a caller this was an error rather than a genuinely empty
                // result set - a valid response always carries one. combatlog:pollruns counts it from
                // there, rather than this service counting for every caller it has (#4173).
                return new SearchAdvancedRunsResponse([], null);
            }

            $runs = [];
            foreach ($json['matches'] as $match) {
                if (!isset($match['data'])) {
                    continue;
                }
                $runs[] = SearchAdvancedRun::fromArray($match['data']);
            }

            // An empty result set returns a scalar `"total": 0` instead of `{"value": n}`
            $total = match (true) {
                isset($json['total']['value'])     => (int)$json['total']['value'],
                is_numeric($json['total'] ?? null) => (int)$json['total'],
                default                            => null,
            };

            return new SearchAdvancedRunsResponse($runs, $total);
        } finally {
            $this->log->searchAdvancedRunsEnd(count($runs ?? []));
        }
    }

    public function getCombatLogSegmentsForRun(Season $season, int $runId): ?CombatLogSegmentsResponse
    {
        $this->log->getCombatLogSegmentsForRunStart($runId);

        $url = sprintf('%s?%s', self::SEGMENTS_URL, http_build_query([
            'season'          => $this->buildSeasonString($season->expansion->shortname, $season->index),
            'keystone_run_id' => $runId,
            'access_key'      => config('keystoneguru.raiderio.api_key'),
        ]));

        try {
            $response = $this->curlGet($url);
            $json     = json_decode($response, true);

            if (!is_array($json) || !isset($json['sourceUserId'], $json['segments']) || !is_array($json['segments'])) {
                // A 404 with this specific message means the run's segments simply haven't been
                // uploaded to Raider.IO yet (#3918) - an expected, recurring state to log distinctly
                // from a genuinely malformed/unexpected response, which stays error-level below.
                // Matching on 'message' too (not just 'statusCode') matters: the upstream API is
                // hapi-style, whose default route-not-found body is also {"statusCode":404,"error":
                // "Not Found","message":"Not Found"} - identical statusCode, but a real integration
                // break (wrong path, unrecognized season) that must still page.
                if (($json['statusCode'] ?? null) === Http::NOT_FOUND
                    && str_contains((string)($json['message'] ?? ''), 'combat log segments')) {
                    $this->log->getCombatLogSegmentsForRunNotYetAvailable($runId, $url, $response);
                } else {
                    $this->log->getCombatLogSegmentsForRunInvalidResponse($runId, $url, $response);
                }

                return null;
            }

            $segments = array_map(
                fn(array $s): CombatLogSegment => new CombatLogSegment(
                    id:          (int)$s['id'],
                    type:        (string)$s['type'],
                    downloadUrl: (string)$s['downloadUrl'],
                ),
                $json['segments'],
            );

            return new CombatLogSegmentsResponse(
                sourceUserId: (int)$json['sourceUserId'],
                segments:     $segments,
            );
        } finally {
            $this->log->getCombatLogSegmentsForRunEnd($runId);
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
