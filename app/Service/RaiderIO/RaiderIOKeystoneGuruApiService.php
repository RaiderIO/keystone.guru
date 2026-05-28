<?php

namespace App\Service\RaiderIO;

use App\Logic\CombatLog\CombatLogVersion;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\RaiderIO\Dtos\CombatLogDownloadResponse;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\RaiderIO\Dtos\HeatmapDataResponse\HeatmapDataResponse;
use App\Service\RaiderIO\Dtos\SearchAdvancedRun;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsResponse;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use App\Service\Traits\Curl;

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

    use Curl;

    public function __construct(
        private readonly SeasonServiceInterface           $seasonService,
        private readonly SeasonAffixGroupServiceInterface $seasonAffixGroupService,
        private readonly CombatLogEventServiceInterface   $combatLogEventService,
    ) {
    }

    public function getHeatmapData(HeatmapDataFilter $heatmapDataFilter): HeatmapDataResponse
    {
        return HeatmapDataResponse::fromArray(
            $this->combatLogEventService->getGridAggregation(
                CombatLogEventFilter::fromHeatmapDataFilter($this->seasonService, $this->seasonAffixGroupService, $heatmapDataFilter),
            )->toArray(),
        );
    }

    public function searchAdvancedRuns(SearchAdvancedRunsFilter $filter): SearchAdvancedRunsResponse
    {
        $zipFiles = glob(base_path('tmp_combatlogs/*.zip'));

        if ($zipFiles === false) {
            return new SearchAdvancedRunsResponse([], 0);
        }

        $dungeonZoneId   = $filter->dungeon?->zone_id ?? self::FAKE_DUNGEON_ZONE_ID;
        $challengeModeId = $filter->dungeon?->challenge_mode_id ?? self::FAKE_CHALLENGE_MODE_ID;
        $specBlizzardIds = $filter->specs->pluck('specialization_id')->map('intval')->values()->all();
        $memberSpecIds   = !empty($specBlizzardIds) ? $specBlizzardIds : self::FAKE_SPEC_IDS;

        $runs = [];
        foreach ($zipFiles as $index => $filePath) {
            $runs[] = new SearchAdvancedRun(
                id:              $index + 1,
                challengeModeId: $challengeModeId,
                dungeonZoneId:   $dungeonZoneId,
                memberSpecIds:   $memberSpecIds,
            );
        }

        return new SearchAdvancedRunsResponse($runs, count($runs));
    }

    public function getCombatLogForRun(int $runId): ?CombatLogDownloadResponse
    {
        $zipFiles = glob(base_path('tmp_combatlogs/*.zip'));

        if ($zipFiles === false) {
            return null;
        }

        $absPath = $zipFiles[array_rand($zipFiles)] ?? null;

        if ($absPath === null) {
            return null;
        }

        return new CombatLogDownloadResponse(
            diskName:         's3_combat_logs',
            s3Bucket:         config('filesystems.disks.s3_combat_logs.bucket'),
            s3Path:           basename($absPath),
            combatLogVersion: max(CombatLogVersion::RETAIL_ALL),
            isFile:           true,
        );
    }
}
