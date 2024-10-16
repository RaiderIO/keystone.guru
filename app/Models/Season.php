<?php

namespace App\Models;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Traits\HasStart;
use App\Models\Traits\SeederModel;
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
 * @property int                       $id
 * @property int                       $expansion_id
 * @property int                       $seasonal_affix_id
 * @property int                       $index
 * @property Carbon                    $start
 * @property int                       $presets
 * @property int                       $affix_group_count
 * @property int                       $start_affix_group_index The index of the affix that was the first affix to be available upon season start
 * @property int                       $key_level_min
 * @property int                       $key_level_max
 * @property string                    $name Dynamic attribute
 * @property string                    $name_med Dynamic attribute
 * @property string                    $name_long Dynamic attribute
 *
 * @property Expansion                 $expansion
 *
 * @property Collection<AffixGroup>    $affixGroups
 * @property Collection<Dungeon>       $dungeons
 * @property Collection<SeasonDungeon> $seasonDungeons
 *
 * @mixin Eloquent
 */
class Season extends CacheModel
{
    use HasStart;
    use SeederModel;

    const SEASON_BFA_S1       = 1;
    const SEASON_BFA_S2       = 2;
    const SEASON_BFA_S3       = 3;
    const SEASON_BFA_S4       = 4;
    const SEASON_SL_S1        = 5;
    const SEASON_SL_S2        = 6;
    const SEASON_LEGION_TW_S1 = 7;
    const SEASON_SL_S3        = 8;
    const SEASON_SL_S4        = 9;
    const SEASON_DF_S1        = 10;
    const SEASON_DF_S2        = 11;
    const SEASON_DF_S3        = 12;
    const SEASON_DF_S4        = 13;
    const SEASON_TWW_S1       = 14;
    const SEASON_TWW_S2       = 15;
    const SEASON_TWW_S3       = 16;
    const SEASON_TWW_S4       = 17;

    const ALL_SEASONS = [
        self::SEASON_BFA_S1,
        self::SEASON_BFA_S2,
        self::SEASON_BFA_S3,
        self::SEASON_BFA_S4,
        self::SEASON_SL_S1,
        self::SEASON_SL_S2,
        self::SEASON_LEGION_TW_S1,
        self::SEASON_SL_S3,
        self::SEASON_SL_S4,
        self::SEASON_DF_S1,
        self::SEASON_DF_S2,
        self::SEASON_DF_S3,
        self::SEASON_DF_S4,
        self::SEASON_TWW_S1,
        self::SEASON_TWW_S2,
        self::SEASON_TWW_S3,
        self::SEASON_TWW_S4,
    ];

    protected $fillable = [
        'expansion_id',
        'seasonal_affix_id',
        'index',
        'start',
        'presets',
        'affix_group_count',
        'start_affix_group_index',
        'key_level_min',
        'key_level_max',
    ];

    public $with = ['expansion', 'affixGroups', 'dungeons'];

    public $timestamps = false;

    protected $appends = ['name', 'name_long'];

    protected $casts = [
        'start'         => 'date',
        'key_level_min' => 'integer',
        'key_level_max' => 'integer',
    ];

    /** @var bool|null Cache for if we're a timewalking season or not */
    private ?bool $isTimewalkingSeason = null;

    public function getNameAttribute(): string
    {
        return __('seasons.name', ['season' => $this->index]);
    }

    public function getNameLongAttribute(): string
    {
        return __('seasons.name_long', ['expansion' => __($this->expansion->name), 'season' => $this->index]);
    }

    public function expansion(): BelongsTo
    {
        return $this->belongsTo(Expansion::class);
    }

    public function affixGroups(): HasMany
    {
        return $this->hasMany(AffixGroup::class);
    }

    public function dungeons(): BelongsToMany
    {
        return $this->belongsToMany(Dungeon::class, 'season_dungeons')->orderBy('season_dungeons.id');
    }

    public function seasonDungeons(): HasMany
    {
        return $this->hasMany(SeasonDungeon::class);
    }

    public function hasDungeon(Dungeon $dungeon): bool
    {
        return $this->seasonDungeons()->where('dungeon_id', $dungeon->id)->exists();
    }

    /**
     * Get a list of unique affixes found in this season.
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
     */
    public function getAffixGroupIterations(): int
    {
        return $this->getAffixGroupIterationsAt(Carbon::now());
    }

    /**
     * Get the amount of full iterations of the entire list of affix groups
     */
    public function getAffixGroupIterationsAt(Carbon $date): int
    {
        $weeksSinceStart = $this->getWeeksSinceStartAt($date);

        // Round down
        return (int)($weeksSinceStart / $this->affixGroups->count());
    }

    /**
     * Get the affix group that is currently active in the region's timezone.
     *
     * @throws Exception
     */
    public function getCurrentAffixGroupInRegion(GameServerRegion $region): ?AffixGroup
    {
        try {
            $result = $this->getAffixGroupAt(Carbon::now(), $region);
        } catch (Exception $exception) {
            Log::error('Error getting current affix group', [
                'exception' => $exception,
                'region'    => $region->short,
            ]);
            throw $exception;
        }

        return $result;
    }

    /**
     * Get the affix group that will be active next week in the region's timezone.
     *
     * @throws Exception
     */
    public function getNextAffixGroupInRegion(GameServerRegion $region): ?AffixGroup
    {
        try {
            $result = $this->getAffixGroupAt(Carbon::now()->addWeek(), $region);
        } catch (Exception $exception) {
            Log::error('Error getting current affix group', [
                'exception' => $exception,
                'region'    => $region->short,
            ]);
            throw $exception;
        }

        return $result;
    }

    /**
     * Get the affix group that is currently active in the user's timezone (if user timezone was set).
     *
     * @throws Exception
     */
    public function getCurrentAffixGroup(): ?AffixGroup
    {
        try {
            $result = $this->getAffixGroupAt(Carbon::now(), GameServerRegion::getUserOrDefaultRegion());
        } catch (Exception $exception) {
            Log::error('Error getting current affix group', [
                'exception' => $exception,
            ]);
            throw new Exception('Error getting current affix group');
        }

        return $result;
    }

    /**
     * Get the affix group that will be active in the user's timezone next week (if user timezone was set).
     *
     * @throws Exception
     */
    public function getNextAffixGroup(): ?AffixGroup
    {
        try {
            $result = $this->getAffixGroupAt(Carbon::now()->addDays(7), GameServerRegion::getUserOrDefaultRegion());
        } catch (Exception $exception) {
            Log::error('Error getting current affix group', [
                'exception' => $exception,
            ]);
            throw new Exception('Error getting current affix group');
        }

        return $result;
    }

    /**
     * Get which affix group is active on this region at a specific point in time.
     *
     * @param Carbon $date The date at which you want to know the affix group.
     * @return AffixGroup|null The affix group that is active at that point in time for your passed timezone.
     *
     * @throws Exception
     * @TODO Move to SeasonService
     */
    public function getAffixGroupAt(Carbon $date, GameServerRegion $region): ?AffixGroup
    {
        /** @var SeasonService $seasonService */
        if ($this->hasTimewalkingEvent()) {
            $timewalkingEventService = resolve(TimewalkingEventService::class);
            $result                  = $timewalkingEventService->getAffixGroupAt($this->expansion, $date);
        } else {
            // Service injection, we do not know ourselves the total iterations done. Our history starts at a date,
            // we do not know anything before that, so we need help
            $seasonService = resolve(SeasonService::class);

            // Get the affix group which occurs after a few weeks and return that
            $affixGroupIndex = $seasonService->getAffixGroupIndexAt($date, $region, $this->expansion);

            // Make sure that the affixes wrap over if we run out
            // $result = $this->affixgroups[$affixGroupIndex % $this->affixgroups->count()] ?? null;
            $result = $affixGroupIndex === null ? null :
                ($affixGroupIndex < $this->affixGroups->count() ? $this->affixGroups[$affixGroupIndex] : null);
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function getPresetForAffixGroup(AffixGroup $affixGroup): int
    {
        $region          = GameServerRegion::getUserOrDefaultRegion();
        $startIndex      = $this->affixGroups->search(
            $this->getAffixGroupAt($this->start($region), $region)
        );
        $affixGroupIndex = $this->affixGroups->search($this->affixGroups->filter(static fn(AffixGroup $affixGroupCandidate) => $affixGroupCandidate->id === $affixGroup->id)->first());

        return $this->presets !== 0 ? ($startIndex + $affixGroupIndex % $this->affixGroups->count()) % $this->presets + 1 : 0;
    }

    /**
     * Get the current preset (if any) at a specific date.
     *
     * @return int The preset at the passed date.
     */
    public function getPresetAtDate(Carbon $date): int
    {
        // Only if the current season has presets do we calculate, otherwise return 0
        return $this->presets !== 0 ? $this->getWeeksSinceStartAt($date) % $this->presets : 0;
    }

    private function hasTimewalkingEvent(): bool
    {
        if ($this->isTimewalkingSeason !== null) {
            return $this->isTimewalkingSeason;
        }

        return $this->isTimewalkingSeason = $this->expansion->hasTimewalkingEvent();
    }
}
