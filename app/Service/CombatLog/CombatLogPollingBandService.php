<?php

namespace App\Service\CombatLog;

use App\Models\Season;
use App\Service\CombatLog\Dtos\KeyLevelBand;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class CombatLogPollingBandService implements CombatLogPollingBandServiceInterface
{
    private const string MAX_KEY_LEVEL_CACHE_KEY = 'combatlog:pollruns:max_key_level:%d:%s';

    private const string MAX_KEY_LEVEL_LAST_KNOWN_CACHE_KEY = 'combatlog:pollruns:max_key_level_last_known:%d';

    private const int MAX_KEY_LEVEL_LAST_KNOWN_TTL_DAYS = 60;

    public function __construct(
        private readonly RaiderIOApiServiceInterface $raiderIOApiService,
    ) {
    }

    public function getMaxKeyLevel(Season $season): int
    {
        $cached = Cache::get($this->getMaxKeyLevelCacheKey($season));

        if (is_int($cached)) {
            return $cached;
        }

        try {
            $maxKeyLevel = $this->probeMaxKeyLevel($season);
        } catch (Throwable $throwable) {
            $lastKnown = Cache::get($this->getMaxKeyLevelLastKnownCacheKey($season));

            Log::warning('combatlog:pollruns - probing the max key level failed', [
                'season'     => $season->id,
                'last_known' => $lastKnown,
                'exception'  => $throwable->getMessage(),
            ]);

            // Fail closed: without a probe result the top band collapses to the probe ceiling,
            // which matches no runs. Parsing nothing extra beats always parsing everything.
            return is_int($lastKnown) ? $lastKnown : $this->getProbeLevelCeiling();
        }

        Cache::put($this->getMaxKeyLevelCacheKey($season), $maxKeyLevel, Carbon::now()->addDays(8));
        Cache::put(
            $this->getMaxKeyLevelLastKnownCacheKey($season),
            $maxKeyLevel,
            Carbon::now()->addDays(self::MAX_KEY_LEVEL_LAST_KNOWN_TTL_DAYS),
        );

        return $maxKeyLevel;
    }

    public function getTopBand(Season $season): KeyLevelBand
    {
        $levelsBelowMax = (int)config('keystoneguru.raider_io.combat_log_polling.top_band.levels_below_max');

        return new KeyLevelBand(
            max($this->getMaxKeyLevel($season) - $levelsBelowMax, $this->getBandLevelMin()),
            null,
        );
    }

    public function getSpreadBands(Season $season): array
    {
        $levelMin = $this->getBandLevelMin();
        $width    = max(1, (int)config('keystoneguru.raider_io.combat_log_polling.bands.width'));
        $topFloor = $this->getTopBand($season)->min;

        $bands = [];

        for ($min = $levelMin; $min < $topFloor; $min += $width) {
            $bands[] = new KeyLevelBand($min, min($min + $width - 1, $topFloor - 1));
        }

        return $bands;
    }

    public function getSpreadBandForHour(Season $season, int $hour): ?KeyLevelBand
    {
        $bands = $this->getSpreadBands($season);

        if ($bands === []) {
            return null;
        }

        return $bands[$hour % count($bands)];
    }

    /**
     * Walks the keystone levels to find the highest one that is still being played in volume.
     * Seeded from the previously found level so the common case costs a handful of API calls
     * instead of walking all the way down from the ceiling.
     */
    private function probeMaxKeyLevel(Season $season): int
    {
        $levelMin = $this->getBandLevelMin();
        $ceiling  = $this->getProbeLevelCeiling();

        $lastKnown = Cache::get($this->getMaxKeyLevelLastKnownCacheKey($season));
        $level     = is_int($lastKnown) ? min(max($lastKnown, $levelMin), $ceiling) : $ceiling;

        if ($this->isPlayedInVolume($season, $level)) {
            while ($level < $ceiling && $this->isPlayedInVolume($season, $level + 1)) {
                $level++;
            }

            return $level;
        }

        while ($level > $levelMin) {
            $level--;

            if ($this->isPlayedInVolume($season, $level)) {
                return $level;
            }
        }

        // Nothing at all is being played in volume, which cannot be true of a live season. Fail
        // closed on the ceiling rather than on the minimum level: a top band that starts at the
        // minimum matches every run there is, and the top band is parsed without any budget.
        return $ceiling;
    }

    /**
     * @throws RuntimeException When the upstream run count could not be established.
     */
    private function isPlayedInVolume(Season $season, int $keyLevel): bool
    {
        $minRuns    = (int)config('keystoneguru.raider_io.combat_log_polling.top_band.min_runs_for_level');
        $windowDays = (int)config('keystoneguru.raider_io.combat_log_polling.top_band.probe_window_days');

        $response = $this->raiderIOApiService->searchAdvancedRuns(new SearchAdvancedRunsFilter(
            dungeon:         null,
            season:          $season,
            specs:           collect(),
            completedAtFrom: Carbon::now()->subDays($windowDays),
            completedAtTo:   null,
            mythicLevelMin:  $keyLevel,
            mythicLevelMax:  $keyLevel,
            limit:           1,
            offset:          0,
        ));

        // A malformed or throttled response yields a null total. Reading that as "nobody plays
        // this level" would walk the probe all the way down and cache the result for a week.
        if ($response->total === null) {
            throw new RuntimeException(sprintf('No run count returned for keystone level %d', $keyLevel));
        }

        return $response->total >= $minRuns;
    }

    private function getBandLevelMin(): int
    {
        return (int)config('keystoneguru.raider_io.combat_log_polling.bands.level_min');
    }

    private function getProbeLevelCeiling(): int
    {
        return (int)config('keystoneguru.raider_io.combat_log_polling.top_band.probe_level_ceiling');
    }

    private function getMaxKeyLevelCacheKey(Season $season): string
    {
        $now = Carbon::now();

        return sprintf(self::MAX_KEY_LEVEL_CACHE_KEY, $season->id, sprintf('%d-%d', $now->isoWeekYear, $now->isoWeek));
    }

    private function getMaxKeyLevelLastKnownCacheKey(Season $season): string
    {
        return sprintf(self::MAX_KEY_LEVEL_LAST_KNOWN_CACHE_KEY, $season->id);
    }
}
