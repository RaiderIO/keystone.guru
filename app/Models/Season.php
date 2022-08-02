<?php

namespace App\Models;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Traits\HasStart;
use App\Service\Season\SeasonService;
use App\Service\TimewalkingEvent\TimewalkingEventService;
use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * @property $id int
 * @property $expansion_id int
 * @property $seasonal_affix_id int
 * @property $index int
 * @property $start datetime
 * @property $presets int
 * @property $name string Dynamic attribute
 *
 * @property Expansion $expansion
 * @property Collection|AffixGroup[] $affixgroups
 * @property Collection|Dungeon[] $dungeons
 *
 * @mixin Eloquent
 */
class Season extends CacheModel
{
    use HasStart;

    protected $fillable = ['expansion_id', 'seasonal_affix_id', 'index', 'start', 'presets'];
    public $with = ['expansion', 'affixgroups', 'dungeons'];
    public $timestamps = false;

    protected $appends = ['name'];

    /** @var boolean|null Cache for if we're a timewalking season or not */
    private ?bool $isTimewalkingSeason = null;

    /**
     * @return string
     */
    public function getNameAttribute(): string
    {
        return __('seasons.name', ['expansion' => __($this->expansion->name), 'season' => $this->index]);
    }

    /**
     * @return BelongsTo
     */
    public function expansion(): BelongsTo
    {
        return $this->belongsTo('App\Models\Expansion');
    }

    /**
     * @return HasMany
     */
    public function affixgroups(): HasMany
    {
        return $this->hasMany('App\Models\AffixGroup\AffixGroup');
    }

    /**
     * @return BelongsToMany
     */
    public function dungeons(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Dungeon', 'season_dungeons');
    }

    /**
     * @return HasMany
     */
    public function seasondungeons(): HasMany
    {
        return $this->hasMany(SeasonDungeon::class);
    }

    /**
     * @param Dungeon $dungeon
     * @return bool
     */
    public function hasDungeon(Dungeon $dungeon): bool
    {
        return $this->seasondungeons()->where('dungeon_id', $dungeon->id)->exists();
    }

    /**
     * Get a list of unique affixes found in this season.
     *
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
     * @return AffixGroup|null
     * @throws Exception
     */
    public function getCurrentAffixGroupInRegion(GameServerRegion $region): ?AffixGroup
    {
        try {
            $result = $this->getAffixGroupAt(Carbon::now($region->timezone), $region);
        } catch (Exception $ex) {
            Log::error('Error getting current affix group', [
                'exception' => $ex,
                'region'    => $region->short,
            ]);
            throw $ex;
        }
        return $result;
    }

    /**
     * Get the affix group that will be active next week in the region's timezone.
     *
     * @param GameServerRegion $region
     * @return AffixGroup|null
     * @throws Exception
     */
    public function getNextAffixGroupInRegion(GameServerRegion $region): ?AffixGroup
    {
        try {
            $result = $this->getAffixGroupAt(Carbon::now($region->timezone)->addWeek(), $region);
        } catch (Exception $ex) {
            Log::error('Error getting current affix group', [
                'exception' => $ex,
                'region'    => $region->short,
            ]);
            throw $ex;
        }
        return $result;
    }

    /**
     * Get the affix group that is currently active in the user's timezone (if user timezone was set).
     *
     * @return AffixGroup|null
     * @throws Exception
     */
    public function getCurrentAffixGroup(): ?AffixGroup
    {
        try {
            $result = $this->getAffixGroupAt($this->getUserNow());
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
     * @return AffixGroup|null
     * @throws Exception
     */
    public function getNextAffixGroup(): ?AffixGroup
    {
        try {
            $result = $this->getAffixGroupAt($this->getUserNow()->addDays(7));
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
     * @return AffixGroup|null The affix group that is active at that point in time for your passed timezone.
     * @throws Exception
     */
    public function getAffixGroupAt(Carbon $date, GameServerRegion $region = null): ?AffixGroup
    {
        /** @var SeasonService $seasonService */
        if ($this->hasTimewalkingEvent()) {
            $timewalkingEventService = resolve(TimewalkingEventService::class);
            $result                  = $timewalkingEventService->getAffixGroupAt($this->expansion, $date);
        } else {
            // Service injection, we do not know ourselves the total iterations done. Our history starts at a date,
            // we do not know anything before that so we need help
            $seasonService = resolve(SeasonService::class);

            // Get the affix group which occurs after a few weeks and return that
            $affixGroupIndex = $seasonService->getAffixGroupIndexAt($date);

            // Make sure that the affixes wrap over if we run out
            // $result = $this->affixgroups[$affixGroupIndex % $this->affixgroups->count()] ?? null;
            $result = $affixGroupIndex < $this->affixgroups->count() ? $this->affixgroups[$affixGroupIndex] : null;
        }

        return $result;
    }

    /**
     * @param AffixGroup $affixGroup
     * @return int
     * @throws Exception
     */
    public function getPresetForAffixGroup(AffixGroup $affixGroup): int
    {
        $startIndex      = $this->affixgroups->search(
            $this->getAffixGroupAt($this->start())
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

    /**
     * @return bool
     */
    private function hasTimewalkingEvent(): bool
    {
        if ($this->isTimewalkingSeason !== null) {
            return $this->isTimewalkingSeason;
        }

        return $this->isTimewalkingSeason = $this->expansion->hasTimewalkingEvent();
    }
}
