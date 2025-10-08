<?php

namespace App\Models;

use App\Models\Traits\SeederModel;
use App\Service\Cache\CacheServiceInterface;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/**
 * @property int        $id
 * @property string     $short
 * @property string     $name
 * @property Carbon     $epoch_start
 * @property string     $timezone
 * @property int        $reset_day_offset   ISO-8601 numeric representation of the day of the week
 * @property string     $reset_hours_offset
 * @property Collection $users
 *
 * @mixin Eloquent
 */
class GameServerRegion extends CacheModel
{
    use SeederModel;

    // Blizzard changed the reset time on November 16th:
    //
    // Weekly Reset Time Changing to 05:00 CET on 16 November
    //
    // https://eu.forums.blizzard.com/en/wow/t/weekly-reset-time-changing-to-0500-cet-on-16-november/398498
    // the date at which the EU epoch change kicks in
    const EU_EPOCH_CHANGE_STARTED_AT_DATE = '2022-11-16 04:00:00';

    // The base date we return when requesting a date using the new epoch
    const EU_EPOCH_CHANGE_DATE = '2005-12-28 04:00:00';

    // The period that the EU epoch change started at
    const EU_EPOCH_CHANGE_PERIOD = 881;

    public const AMERICAS = 'us';
    public const EUROPE   = 'eu';
    public const CHINA    = 'cn';
    public const TAIWAN   = 'tw';
    public const KOREA    = 'kr';
    public const WORLD    = 'world';

    public const DEFAULT_REGION = GameServerRegion::AMERICAS;

    public const ALL = [
        self::AMERICAS => 1,
        self::EUROPE   => 2,
        self::CHINA    => 3,
        self::TAIWAN   => 4,
        self::KOREA    => 5,
        self::WORLD    => 6,
    ];

    protected $fillable = [
        'short',
        'name',
        'epoch_start',
        'timezone',
        'reset_day_offset',
        'reset_hours_offset',
    ];

    protected $casts = [
        'epoch_start' => 'datetime',
    ];

    public $timestamps = false;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return GameServerRegion Gets the default region.
     */
    public static function getUserOrDefaultRegion(): GameServerRegion
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->game_server_region_id > 0 && $user->gameServerRegion !== null) {
                return $user->gameServerRegion;
            }
        }

        /** @var CacheServiceInterface $cacheService */
        $cacheService = App::make(CacheServiceInterface::class);

        return $cacheService->remember(
            'default_region',
            static fn() => GameServerRegion::where('short', self::DEFAULT_REGION)->first(),
            config('keystoneguru.cache.default_game_region.ttl')
        );
    }

    /**
     * Get the leaderboard period based on the region and a given date.
     *
     * @param  Carbon $dateTime
     * @return int
     */
    public function getKeystoneLeaderboardPeriod(Carbon $dateTime): int
    {
        $epoch = self::getRegionEpochByDate($dateTime);

        return $epoch->diffInWeeks($dateTime);
    }

    /**
     * Get the epoch date for a region based on the given date.
     *
     * @param  Carbon      $dateTime
     * @return Carbon|null
     */
    public function getRegionEpochByDate(Carbon $dateTime): ?Carbon
    {
        if ($this->short === self::EUROPE && $dateTime >= Carbon::parse(self::EU_EPOCH_CHANGE_STARTED_AT_DATE)) {
            return Carbon::parse(self::EU_EPOCH_CHANGE_DATE);
        }

        return Carbon::parse($this->epoch_start);
    }
}
