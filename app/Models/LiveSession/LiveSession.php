<?php

namespace App\Models\LiveSession;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Traits\GeneratesPublicKey;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\LiveSession\LiveSessionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int    $id
 * @property int    $dungeon_route_id
 * @property int    $user_id
 * @property string $public_key
 *
 * @property User                                                $user
 * @property DungeonRoute|null                                   $dungeonRoute
 * @property LiveSessionCombatLogBuffer                          $combatLogBuffer
 * @property EloquentCollection<int, LiveSessionOverpulledEnemy> $overpulledEnemies
 * @property EloquentCollection<int, LiveSessionKilledEnemy>     $killedEnemies
 * @property EloquentCollection<int, LiveSessionObsoleteEnemy>   $obsoleteEnemies
 * @property EloquentCollection<int, LiveSessionPlayerPosition>  $playerPositions
 * @property Carbon|null                                         $expires_at
 *
 * @mixin Eloquent
 */
class LiveSession extends Model
{
    /** @use HasFactory<LiveSessionFactory> */
    use HasFactory;
    use GeneratesPublicKey;

    protected static function newFactory(): LiveSessionFactory
    {
        return LiveSessionFactory::new();
    }

    protected $fillable = [
        'dungeon_route_id',
        'user_id',
        'public_key',
        'expires_at',
    ];

    protected $with = [
        'user',
        'dungeonRoute',
    ];

    /**
     * https://stackoverflow.com/a/34485411/771270
     */
    #[Override]
    public function getRouteKeyName(): string
    {
        return 'public_key';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the dungeon route that this live session is attached to.
     */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    public function combatLogBuffer(): HasOne
    {
        return $this->hasOne(LiveSessionCombatLogBuffer::class);
    }

    public function overpulledEnemies(): HasMany
    {
        return $this->hasMany(LiveSessionOverpulledEnemy::class);
    }

    public function killedEnemies(): HasMany
    {
        return $this->hasMany(LiveSessionKilledEnemy::class);
    }

    public function obsoleteEnemies(): HasMany
    {
        return $this->hasMany(LiveSessionObsoleteEnemy::class);
    }

    public function playerPositions(): HasMany
    {
        return $this->hasMany(LiveSessionPlayerPosition::class);
    }

    /**
     * @return EloquentCollection<int, Enemy>
     */
    public function getEnemies(): EloquentCollection
    {
        return Enemy::select('enemies.*')
            ->join('live_session_overpulled_enemies', static function (JoinClause $clause) {
                $clause->on('live_session_overpulled_enemies.npc_id', 'enemies.npc_id')
                    ->on('live_session_overpulled_enemies.mdt_id', 'enemies.mdt_id');
            })
            ->join('live_sessions', 'live_sessions.id', 'live_session_overpulled_enemies.live_session_id')
            ->join('dungeon_routes', 'dungeon_routes.id', 'live_sessions.dungeon_route_id')
            ->whereColumn('enemies.mapping_version_id', 'dungeon_routes.mapping_version_id')
            ->where('live_session_overpulled_enemies.live_session_id', $this->id)
            ->get();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && Carbon::createFromTimeString($this->expires_at)->isPast();
    }

    public function getExpiresInSeconds(): ?int
    {
        return $this->expires_at === null ? null : (int)Carbon::createFromTimeString($this->expires_at)->diffInSeconds(now(), true);
    }

    public function getExpiresInHoursSeconds(): ?string
    {
        return $this->expires_at === null ? null :
            now()->diffForHumans(Carbon::createFromTimeString($this->expires_at), CarbonInterface::DIFF_ABSOLUTE, true);
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        // Delete route properly if it gets deleted
        static::deleting(static function (LiveSession $item) {
            $item->combatLogBuffer->delete();
            $item->overpulledEnemies()->delete();
            $item->killedEnemies()->delete();
            $item->obsoleteEnemies()->delete();
            $item->playerPositions()->delete();
        });
    }
}
