<?php

namespace App\Models\LiveSession;

use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\Npc\Npc;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\JoinClause;

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
        'enemy_id',
        'kill_zone_id',
    ];

    public $timestamps = false;

    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }

    public function killZone(): BelongsTo
    {
        return $this->belongsTo(KillZone::class);
    }

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
}
