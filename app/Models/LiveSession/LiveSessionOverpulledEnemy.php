<?php

namespace App\Models\LiveSession;

use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\Npc\Npc;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\JoinClause;
use Override;

/**
 * @property int $id
 * @property int $live_session_id
 * @property int $kill_zone_id
 * @property int $npc_id
 * @property int $mdt_id
 *
 * @property LiveSession $liveSession
 * @property KillZone    $killZone
 * @property Npc         $npc
 * @property Enemy       $enemy
 *
 * @mixin Eloquent
 */
class LiveSessionOverpulledEnemy extends Model
{
    protected $fillable = [
        'live_session_id',
        'kill_zone_id',
        'npc_id',
        'mdt_id',
    ];

    protected $visible = [
        'kill_zone_id',
    ];

    public $timestamps = false;

    /** @return BelongsTo<LiveSession, $this> */
    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }

    /** @return BelongsTo<KillZone, $this> */
    public function killZone(): BelongsTo
    {
        return $this->belongsTo(KillZone::class);
    }

    /** @return BelongsTo<Npc, $this> */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function getEnemy(): Enemy
    {
        /** @var Enemy $result */
        $result = Enemy::select('enemies.*')
            ->join('live_session_overpulled_enemies', static function (JoinClause $clause) {
                $clause->on('live_session_overpulled_enemies.npc_id', 'enemies.npc_id')
                    ->on('live_session_overpulled_enemies.mdt_id', 'enemies.mdt_id');
            })
            ->join('live_sessions', 'live_sessions.id', 'live_session_overpulled_enemies.live_session_id')
            ->join('dungeon_routes', 'dungeon_routes.id', 'live_sessions.dungeon_route_id')
            ->whereColumn('enemies.mapping_version_id', 'dungeon_routes.mapping_version_id')
            ->where('live_session_overpulled_enemies.npc_id', $this->npc_id)
            ->where('live_session_overpulled_enemies.mdt_id', $this->mdt_id)
            ->first();

        return $result;
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        // Dual-write into the legacy table: the previous release reads overpulled enemies from `overpulled_enemies`
        // while it is still serving requests, or again after a rollback.
        static::saved(static function (LiveSessionOverpulledEnemy $overpulledEnemy) {
            OverpulledEnemy::query()->updateOrCreate([
                'live_session_id' => $overpulledEnemy->getOriginal('live_session_id') ?? $overpulledEnemy->live_session_id,
                'npc_id'          => $overpulledEnemy->getOriginal('npc_id') ?? $overpulledEnemy->npc_id,
                'mdt_id'          => $overpulledEnemy->getOriginal('mdt_id') ?? $overpulledEnemy->mdt_id,
            ], $overpulledEnemy->only(['live_session_id', 'kill_zone_id', 'npc_id', 'mdt_id']));
        });

        static::deleted(static function (LiveSessionOverpulledEnemy $overpulledEnemy) {
            OverpulledEnemy::query()
                ->where('live_session_id', $overpulledEnemy->live_session_id)
                ->where('npc_id', $overpulledEnemy->npc_id)
                ->where('mdt_id', $overpulledEnemy->mdt_id)
                ->delete();
        });
    }
}
