<?php

namespace App\Service\RaiderIO;

use App\Models\Season;
use App\Models\User;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
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
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * This service mocks the RaiderIO API service and returns data from Keystone.guru instead for the interim
 */
class RaiderIOKeystoneGuruApiService implements RaiderIOApiServiceInterface
{
    /** Hardcoded retail spec IDs used to populate fake runs for local testing. */
    private const array FAKE_SPEC_IDS = [66, 70, 105, 250, 269];

    /** Hardcoded challenge mode ID used to populate fake runs (Seat of the Triumvirate). */
    private const int FAKE_CHALLENGE_MODE_ID = 239;

    /** Hardcoded dungeon zone ID matching the challenge mode ID above. */
    private const int FAKE_DUNGEON_ZONE_ID = 8910;

    /** Faction used to populate fake runs that were not asked for a specific one. */
    private const RaiderIOFaction FAKE_FACTION = RaiderIOFaction::Alliance;

    /**
     * Per continent map_id: the open-world grid response, keyed by event type, each entry shaped exactly like the
     * response of the Raider.IO heatmap grid endpoint with floorsAsArray on.
     */
    private const string CONTINENT_HEATMAP_FIXTURE_PATH = 'data/raiderio_mock/heatmap_grid_map_%d.json';

    public function __construct(
        private readonly SeasonServiceInterface         $seasonService,
        private readonly CombatLogEventServiceInterface $combatLogEventService,
        private readonly CoordinatesServiceInterface    $coordinatesService,
    ) {
    }

    public function getHeatmapData(HeatmapDataFilter $heatmapDataFilter, ?User $user = null): HeatmapDataResponse
    {
        if ($heatmapDataFilter->getDungeon()->isContinent()) {
            return $this->getContinentHeatmapData($heatmapDataFilter, $user);
        }

        return HeatmapDataResponse::fromArray(
            $this->combatLogEventService->getGridAggregation(
                CombatLogEventFilter::fromHeatmapDataFilter($this->seasonService, $heatmapDataFilter),
            )->toArray(),
        );
    }

    public function searchAdvancedRuns(SearchAdvancedRunsFilter $filter): SearchAdvancedRunsResponse
    {
        $zipFiles = $this->getS3ZipFiles();

        if (empty($zipFiles)) {
            return new SearchAdvancedRunsResponse([], 0);
        }

        $dungeonZoneId   = $filter->dungeon?->zone_id ?? self::FAKE_DUNGEON_ZONE_ID; // @phpstan-ignore nullsafe.neverNull
        $challengeModeId = $filter->dungeon?->challenge_mode_id ?? self::FAKE_CHALLENGE_MODE_ID; // @phpstan-ignore nullsafe.neverNull
        $specBlizzardIds = $filter->specs->pluck('specialization_id')->map('intval')->values()->all();
        $memberSpecIds   = !empty($specBlizzardIds) ? $specBlizzardIds : self::FAKE_SPEC_IDS;
        // Reflected back the same way the dungeon and the specs are: the poller records a fake run
        // against the very criteria it searched with, so a filter this mock ignored would leave that
        // criterion's budget stuck at zero for as long as the mock is enabled.
        $faction = RaiderIOFaction::fromFaction($filter->faction) ?? self::FAKE_FACTION;

        $runs = [];
        foreach ($zipFiles as $index => $filePath) {
            $runs[] = new SearchAdvancedRun(
                id:              $index + 1,
                challengeModeId: $challengeModeId,
                dungeonZoneId:   $dungeonZoneId,
                memberSpecIds:   $memberSpecIds,
                mythicLevel:     $filter->mythicLevelMin,
                affixes:         [],
                faction:         $faction->value,
            );
        }

        return new SearchAdvancedRunsResponse($runs, count($runs));
    }

    public function getCombatLogSegmentsForRun(Season $season, int $runId): ?CombatLogSegmentsResponse
    {
        $zipFiles = $this->getS3ZipFiles();

        if (empty($zipFiles)) {
            return null;
        }

        $s3Path = $zipFiles[array_rand($zipFiles)] ?? null;

        if ($s3Path === null) {
            return null;
        }

        try {
            $downloadUrl = Storage::disk('s3_combat_logs')->temporaryUrl($s3Path, now()->addMinutes(30));
        } catch (RuntimeException) {
            $downloadUrl = Storage::disk('s3_combat_logs')->url($s3Path);
        }

        return new CombatLogSegmentsResponse(
            sourceUserId: 0,
            segments:     [
                new CombatLogSegment(
                    id:          1,
                    type:        'combat_log',
                    downloadUrl: $downloadUrl,
                ),
            ],
        );
    }

    /**
     * Runs the fixture through the same pipeline RaiderIOApiService runs the real response through.
     */
    private function getContinentHeatmapData(HeatmapDataFilter $heatmapDataFilter, ?User $user): HeatmapDataResponse
    {
        $fixturePath = database_path(sprintf(self::CONTINENT_HEATMAP_FIXTURE_PATH, $heatmapDataFilter->getDungeon()->map_id));
        /** @var array<string, array{gridsByFloor: array<int, array<int, int|float>>, numRuns: int, maxSamplesInGrid: int}> $fixture */
        $fixture = file_exists($fixturePath) ? json_decode((string)file_get_contents($fixturePath), true) : [];

        $response = $fixture[$heatmapDataFilter->getEventType()->value] ?? [
            'gridsByFloor'     => [],
            'numRuns'          => 0,
            'maxSamplesInGrid' => 0,
        ];

        return HeatmapDataResponse::fromArray(
            new RaiderIOHeatmapGridResponse(
                $this->coordinatesService,
                CombatLogEventFilter::fromHeatmapDataFilter($this->seasonService, $heatmapDataFilter),
                $response['gridsByFloor'],
                $response['numRuns'],
                $response['maxSamplesInGrid'],
                sprintf(
                    '%s?%s',
                    config('keystoneguru.heatmap.api.continent_grid_url'),
                    http_build_query($heatmapDataFilter->toArray()),
                ),
                true,
                $user,
            )->toArray(),
        );
    }

    /**
     * @return string[]
     */
    private function getS3ZipFiles(): array
    {
        try {
            return collect(Storage::disk('s3_combat_logs')->files(''))
                ->filter(fn(string $path): bool => str_ends_with($path, '.zip'))
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }
}
