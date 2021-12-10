<?php

namespace App\Models;

use App\Models\Traits\HasStart;
use App\Service\Season\SeasonService;
use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * @property $id int
 * @property $seasonal_affix_id int
 * @property $event string|null
 * @property $start datetime
 * @property $presets int
 *
 * @property Collection|AffixGroup[] $affixgroups
 *
 * @mixin Eloquent
 */
class Season extends CacheModel
{
    use HasStart;

    public $with = ['affixgroups'];
    public $timestamps = false;

    /**
     * @return HasMany
     */
    public function affixgroups(): HasMany
    {
        return $this->hasMany('App\Models\AffixGroup')->whereNull('event');
    }

    /**
     * @return Collection
     */
    public function getFeaturedAffixes(): Collection
    {
        return Affix::query()
            ->selectRaw('affixes.*')
            ->join('affix_group_couplings', 'affix_group_couplings.affix_id', '=', 'affixes.id')
            ->join('affix_groups', 'affix_groups.id', '=', 'affix_group_couplings.affix_group_id')
            ->where('affix_groups.season_id', $this->id)
            ->get()
            ->unique('id');
    }

    /**
     * Get the amount of weeks that have passed since the start of the M+ season, on a specific date.
     * @param Carbon $date
     * @return int
     */
    public function getWeeksSinceStartAt(Carbon $date): int
    {
        $start = $this->start();

        // Target date
        $targetTime = Carbon::create($date->year, $date->month, $date->day, $date->hour, null, null, $date->timezone);

        // Get the week difference
        return $start->diffInWeeks($targetTime);
    }

    /**
     * Get the amount of full iterations of the entire list of affix groups that this season has done, since the start
     * of the season.
     * @return int
     */
    public function getAffixGroupIterations(): int
    {
        return $this->getAffixGroupIterationsAt($this->getUserNow());
    }

    /**
     * Get the amount of full iterations of the entire list of affix groups
     *
     * @param Carbon $date
     * @return int
     */
    public function getAffixGroupIterationsAt(Carbon $date): int
    {
        $weeksSinceStart = $this->getWeeksSinceStartAt($date);

        // Round down
        return (int)($weeksSinceStart / $this->affixgroups->count());
    }

    /**
     * Get the affix group that is currently active in the region's timezone.
     *
     * @param GameServerRegion $region
     * @return AffixGroup
     * @throws Exception
     */
    public function getCurrentAffixGroupInRegion(GameServerRegion $region): AffixGroup
    {
        try {
            $result = $this->getAffixGroupAtTime(Carbon::now($region->timezone), $region);
        } catch (Exception $ex) {
            Log::error('Error getting current affix group', [
                'exception' => $ex,
                'region'    => $region->short,
            ]);
            throw new Exception('Error getting current affix group');
        }
        return $result;
    }

    /**
     * Get the affix group that will be active next week in the region's timezone.
     *
     * @param GameServerRegion $region
     * @return AffixGroup
     * @throws Exception
     */
    public function getNextAffixGroupInRegion(GameServerRegion $region): AffixGroup
    {
        try {
            $result = $this->getAffixGroupAtTime(Carbon::now($region->timezone)->addDays(7), $region);
        } catch (Exception $ex) {
            Log::error('Error getting current affix group', [
                'exception' => $ex,
                'region'    => $region->short,
            ]);
            throw new Exception('Error getting current affix group');
        }
        return $result;
    }

    /**
     * Get the affix group that is currently active in the user's timezone (if user timezone was set).
     *
     * @return AffixGroup
     * @throws Exception
     */
    public function getCurrentAffixGroup(): AffixGroup
    {
        try {
            $result = $this->getAffixGroupAtTime($this->getUserNow());
        } catch (Exception $ex) {
            Log::error('Error getting current affix group', [
                'exception' => $ex,
            ]);
            throw new Exception('Error getting current affix group');
        }
        return $result;
    }

    /**
     * Get the affix group that will be active in the user's timezone next week (if user timezone was set).
     *
     * @return AffixGroup
     * @throws Exception
     */
    public function getNextAffixGroup(): AffixGroup
    {
        try {
            $result = $this->getAffixGroupAtTime($this->getUserNow()->addDays(7));
        } catch (Exception $ex) {
            Log::error('Error getting current affix group', [
                'exception' => $ex,
            ]);
            throw new Exception('Error getting current affix group');
        }
        return $result;
    }


    /**
     * Get which affix group is active on this region at a specific point in time.
     *
     * @param Carbon $date The date at which you want to know the affix group.
     * @param GameServerRegion|null $region
     * @return AffixGroup The affix group that is active at that point in time for your passed timezone.
     * @throws Exception
     */
    public function getAffixGroupAtTime(Carbon $date, GameServerRegion $region = null): AffixGroup
    {
        /** @var SeasonService $seasonService */
        $start = $this->start($region);
        if ($date->lt($start)) {
            throw new Exception('Cannot find an affix group of this season before it\'s started!');
        }

        // Service injection, we do not know ourselves the total iterations done. Our history starts at a date,
        // we do not know anything before that so we need help
        $seasonService = resolve(SeasonService::class);

        // Get the affix group which occurs after a few weeks and return that
        return $this->affixgroups[$seasonService->getAffixGroupIndexAt($date)];
    }

    /**
     * @param AffixGroup $affixGroup
     * @return int
     * @throws Exception
     */
    public function getPresetForAffixGroup(AffixGroup $affixGroup): int
    {
        $startIndex      = $this->affixgroups->search(
            $this->getAffixGroupAtTime($this->start())
        );
        $affixGroupIndex = $this->affixgroups->search($this->affixgroups->filter(function (AffixGroup $affixGroupCandidate) use ($affixGroup) {
            return $affixGroupCandidate->id === $affixGroup->id;
        })->first());

        return $this->presets !== 0 ? ($startIndex + $affixGroupIndex % $this->affixgroups->count()) % $this->presets + 1 : 0;
    }

    /**
     * Get the current preset (if any) at a specific date.
     * @param Carbon $date
     * @return int The preset at the passed date.
     */
    public function getPresetAtDate(Carbon $date): int
    {
        // Only if the current season has presets do we calculate, otherwise return 0
        return $this->presets !== 0 ? $this->getWeeksSinceStartAt($date) % $this->presets : 0;
    }
}
