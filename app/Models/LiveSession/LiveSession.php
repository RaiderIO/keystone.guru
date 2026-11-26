<?php

namespace App\Models\LiveSession;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\Models\Interfaces\BelongsToDungeonRouteInterface;
use App\Models\Traits\GeneratesPublicKey;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Carbon\CarbonInterface;
use Database\Factories\LiveSession\LiveSessionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Override;

/**
 * @property int    $id
 * @property int    $dungeon_route_id
 * @property int    $user_id
 * @property string $public_key
 *
 * @property User                                                $user
 * @property DungeonRoute|null                                   $dungeonRoute
 * @property LiveSessionCombatLogBuffer|null                     $combatLogBuffer
 * @property EloquentCollection<int, LiveSessionCombatLogBatch>  $combatLogBatches
 * @property EloquentCollection<int, LiveSessionOverpulledEnemy> $overpulledEnemies
 * @property EloquentCollection<int, LiveSessionEnemyState>      $enemyStates
 * @property EloquentCollection<int, LiveSessionPlayerPosition>  $playerPositions
 * @property Carbon|null                                         $expires_at
 *
 * @mixin Eloquent
 */
class LiveSession extends Model implements BelongsToDungeonRouteInterface
{
    /** @use HasFactory<LiveSessionFactory> */
    use HasFactory;
    use GeneratesPublicKey;

    /**
     * Every model holding rows that belong to a live session through a `live_session_id` column.
     *
     * @var array<int, class-string<Model>>
     */
    public const array CHILD_MODEL_CLASSES = [
        LiveSessionCombatLogBuffer::class,
        LiveSessionCombatLogBatch::class,
        LiveSessionOverpulledEnemy::class,
        OverpulledEnemy::class,
        LiveSessionEnemyState::class,
        LiveSessionPlayerPosition::class,
    ];

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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the dungeon route that this live session is attached to.
     *
     * @return BelongsTo<DungeonRoute, $this>
     */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /** @return HasOne<LiveSessionCombatLogBuffer, $this> */
    public function combatLogBuffer(): HasOne
    {
        return $this->hasOne(LiveSessionCombatLogBuffer::class);
    }

    /** @return HasMany<LiveSessionCombatLogBatch, $this> */
    public function combatLogBatches(): HasMany
    {
        return $this->hasMany(LiveSessionCombatLogBatch::class);
    }

    /** @return HasMany<LiveSessionOverpulledEnemy, $this> */
    public function overpulledEnemies(): HasMany
    {
        return $this->hasMany(LiveSessionOverpulledEnemy::class);
    }

    /** @return HasMany<LiveSessionEnemyState, $this> */
    public function enemyStates(): HasMany
    {
        return $this->hasMany(LiveSessionEnemyState::class);
    }

    /** @return HasMany<LiveSessionPlayerPosition, $this> */
    public function playerPositions(): HasMany
    {
        return $this->hasMany(LiveSessionPlayerPosition::class);
    }

    /**
     * @return Collection<int, LiveSessionPlayerPosition>
     */
    public function mapContextPlayerPositions(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<int, LiveSessionPlayerPosition> $playerPositions */
        $playerPositions = $this->playerPositions()->with('floor')->get();

        if ($useFacade) {
            foreach ($playerPositions as $playerPosition) {
                if (!$playerPosition->hasValidLatLng()) {
                    continue;
                }

                $convertedLatLng = $coordinatesService->convertMapLocationToFacadeMapLocation(
                    $this->dungeonRoute->mappingVersion,
                    $playerPosition->getLatLng(),
                );

                $playerPosition->setLatLng($convertedLatLng);
            }
        }

        return $playerPositions;
    }

    /**
     * The enemies on this session's route mapping version that match a row of a per-enemy live session table
     * (one with `live_session_id`, `npc_id` and `mdt_id` columns) belonging to this session.
     *
     * @return Builder<Enemy>
     */
    public function enemiesMatchingRowsOf(string $liveSessionEnemyTable): Builder
    {
        return Enemy::query()
            ->join($liveSessionEnemyTable, static function (JoinClause $clause) use ($liveSessionEnemyTable) {
                $clause->on(sprintf('%s.npc_id', $liveSessionEnemyTable), 'enemies.npc_id')
                    ->on(sprintf('%s.mdt_id', $liveSessionEnemyTable), 'enemies.mdt_id');
            })
            ->join('live_sessions', 'live_sessions.id', sprintf('%s.live_session_id', $liveSessionEnemyTable))
            ->join('dungeon_routes', 'dungeon_routes.id', 'live_sessions.dungeon_route_id')
            ->whereColumn('enemies.mapping_version_id', 'dungeon_routes.mapping_version_id')
            ->where(sprintf('%s.live_session_id', $liveSessionEnemyTable), $this->id);
    }

    /**
     * Resolve the overpulled-enemy rows to live Enemy IDs (via the route's mapping version), each paired
     * with the kill zone it was attributed to. The frontend restores overpulled state from this shape.
     *
     * @return Collection<int, array{enemy_id: int, kill_zone_id: int}>
     */
    public function mapContextOverpulledEnemies(): Collection
    {
        return $this->enemiesMatchingRowsOf('live_session_overpulled_enemies')
            ->select('enemies.id', 'live_session_overpulled_enemies.kill_zone_id')
            ->get()
            ->map(static fn(Enemy $enemy): array => [
                'enemy_id'     => (int)$enemy->id,
                'kill_zone_id' => (int)$enemy->getAttribute('kill_zone_id'),
            ]);
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
            foreach (self::CHILD_MODEL_CLASSES as $childModelClass) {
                $childModelClass::query()->where('live_session_id', $item->id)->delete();
            }
        });
    }
}
