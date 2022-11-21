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
use Illuminate\Database\Query\JoinClause;
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
    protected $appends = ['enemies'];

    protected $fillable = [
        'dungeon_route_id',
        'user_id',
        'public_key',
    ];

    protected $with = [
        'user',
        'dungeonroute',
    ];

    use GeneratesPublicKey;

    /**
     * https://stackoverflow.com/a/34485411/771270
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'public_key';
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the dungeon route that this live session is attached to.
     *
     * @return BelongsTo
     */
    public function dungeonroute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class, 'dungeon_route_id');
    }

    /**
     * @return HasMany
     */
    public function overpulledenemies(): HasMany
    {
        return $this->hasMany(OverpulledEnemy::class);
    }

    /**
     * @return Collection|Enemy[]
     */
    public function getEnemies(): Collection
    {
        return Enemy::select('enemies.*')
            ->join('overpulled_enemies', function (JoinClause $clause) {
                $clause->on('overpulled_enemies.npc_id', 'enemies.npc_id')
                    ->on('overpulled_enemies.mdt_id', 'enemies.mdt_id');
            })
            ->join('live_sessions', 'live_sessions.id', 'overpulled_enemies.live_session_id')
            ->join('dungeon_routes', 'dungeon_routes.id', 'live_sessions.dungeon_route_id')
            ->whereColumn('enemies.mapping_version_id', 'dungeon_routes.mapping_version_id')
            ->where('overpulled_enemies.live_session_id', $this->id)
            ->get();
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
        static::deleting(function (LiveSession $item) {
            $item->overpulledenemies()->delete();
        });
    }
}
