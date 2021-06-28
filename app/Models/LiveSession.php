<?php

namespace App\Models;

use App\Models\Enemies\OverpulledEnemy;
use App\Models\Traits\GeneratesPublicKey;
use App\User;
use Carbon\CarbonInterface;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $user_id
 * @property string $public_key
 *
 * @property User $user
 * @property DungeonRoute $dungeonroute
 * @property Collection|OverpulledEnemy[] $overpulledenemies
 *
 * @property Carbon $expires_at
 *
 * @mixin Eloquent
 */
class LiveSession extends Model
{
    protected $fillable = [
        'dungeon_route_id',
        'user_id',
        'public_key'
    ];

    protected $with = [
        'user',
        'dungeonroute'
    ];

    use GeneratesPublicKey;

    /**
     * https://stackoverflow.com/a/34485411/771270
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'public_key';
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get the dungeon route that this killzone is attached to.
     *
     * @return BelongsTo
     */
    function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }

    /**
     * @return HasMany
     */
    public function overpulledenemies()
    {
        return $this->hasMany('App\Models\Enemies\OverpulledEnemy');
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && Carbon::createFromTimeString($this->expires_at)->isPast();
    }

    /**
     * @return int|null
     */
    public function getExpiresInSeconds(): ?int
    {
        return $this->expires_at === null ? null : Carbon::createFromTimeString($this->expires_at)->diffInSeconds(now());
    }

    /**
     * @return string|null
     */
    public function getExpiresInHoursSeconds(): ?string
    {
        return $this->expires_at === null ? null :
            now()->diffForHumans(Carbon::createFromTimeString($this->expires_at), CarbonInterface::DIFF_ABSOLUTE, true);
    }


    public static function boot()
    {
        parent::boot();

        // Delete route properly if it gets deleted
        static::deleting(function (LiveSession $item)
        {
            $item->overpulledenemies()->delete();
        });
    }
}
