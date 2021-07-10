<?php

namespace App\Models;

use App\Service\Season\SeasonService;
use Eloquent;
use Exception;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @property $id int
 * @property $seasonal_affix_id int
 * @property $start datetime
 * @property $presets int
 *
 * @property Collection|AffixGroup[] $affixgroups
 *
 * @mixin Eloquent
 */
class Season extends CacheModel
{
    public $with = ['affixgroups'];
    public $timestamps = false;

    /**
     * @return Repository|mixed|string
     */
    private function _getUserTimezone()
    {
        // Find the timezone that makes the most sense
        $timezone = config('app.timezone');

        // But if logged in, get the user's timezone instead
        if (Auth::check()) {
            $timezone = Auth::user()->timezone;
        }
        return $timezone;
    }

    /**
     * @return Carbon Get a date of now with the timezone set properly.
     * @todo This is a copy of the service function
     */
    private function _getNow(): Carbon
    {
        return Carbon::now($this->_getUserTimezone());
    }

    /**
     * @return HasMany
     */
    public function affixgroups(): HasMany
    {
        return $this->hasMany('App\Models\AffixGroup');
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
     * @return Carbon The start date of this season.
     */
    public function start(): Carbon
    {
        $start = Carbon::createFromTimeString($this->start, 'UTC');

        $region = GameServerRegion::getUserOrDefaultRegion();
        $start->startOfWeek();
        // -1, offset 1 means monday, which we're already at
        $start->addDays($region->reset_day_offset - 1);
        $start->addHours($region->reset_hours_offset);
        $start->setTimezone($this->_getUserTimezone());

        return $start;
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
        return $this->getAffixGroupIterationsAt($this->_getNow());
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
     * Get the affix group that is currently active in the user's timezone (if user timezone was set).
     *
     * @return AffixGroup|boolean
     */
    public function getCurrentAffixGroup()
    {
        $result = false;
        try {
            $result = $this->getAffixGroupAtTime($this->_getNow());
        } catch (Exception $ex) {
            Log::error('Error getting current affix group: ' . $ex->getMessage());
        }
        return $result;
    }

    /**
     * Get the affix group that will be active in the user's timezone next week (if user timezone was set).
     *
     * @return AffixGroup|boolean
     */
    public function getNextAffixGroup()
    {
        $result = false;
        try {
            $result = $this->getAffixGroupAtTime($this->_getNow()->addDays(7));
        } catch (Exception $ex) {
            Log::error('Error getting current affix group: ' . $ex->getMessage());
        }
        return $result;
    }


    /**
     * Get which affix group is active on this region at a specific point in time.
     *
     * @param Carbon $date The date at which you want to know the affix group.
     * @return AffixGroup The affix group that is active at that point in time for your passed timezone.
     * @throws Exception
     */
    public function getAffixGroupAtTime(Carbon $date): AffixGroup
    {
        /** @var SeasonService $seasonService */
        $start = $this->start();
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
     * Get the current preset (if any) at a specific date.
     * @param Carbon $date
     * @return int The preset at the passed date.
     */
    public function getPresetAt(Carbon $date): int
    {
        // Only if the current season has presets do we calculate, otherwise return 0
        return $this->presets !== 0 ? $this->getWeeksSinceStartAt($date) % $this->presets + 1 : 0;
    }

    /**
     * @return array
     */
    public function getSeasonalIndexesAsLetters(): array
    {
        $seasonalIndexLetters = [];
        foreach ($this->affixgroups as $affixGroup) {
            $seasonalIndexLetter = $affixGroup->getSeasonalIndexAsLetter();
            if ($seasonalIndexLetter !== null) {
                $seasonalIndexLetters[] = $seasonalIndexLetter;
            }
        }
        return array_values(array_unique($seasonalIndexLetters));
    }
}
