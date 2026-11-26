<?php

namespace App\Models\LiveSession;

use App\Models\Npc\Npc;
use Database\Factories\LiveSession\LiveSessionEnemyStateFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An enemy (identified by npc_id + mdt_id, so it survives a mapping version change) in a given state for a live
 * session.
 *
 * @property int                       $id
 * @property int                       $live_session_id
 * @property LiveSessionEnemyStateType $state
 * @property int                       $npc_id
 * @property int                       $mdt_id
 *
 * @property LiveSession $liveSession
 * @property Npc         $npc
 *
 * @mixin Eloquent
 */
class LiveSessionEnemyState extends Model
{
    /** @use HasFactory<LiveSessionEnemyStateFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'live_session_id',
        'state',
        'npc_id',
        'mdt_id',
    ];

    protected function casts(): array
    {
        return [
            'state' => LiveSessionEnemyStateType::class,
        ];
    }

    protected static function newFactory(): LiveSessionEnemyStateFactory
    {
        return LiveSessionEnemyStateFactory::new();
    }

    /** @return BelongsTo<LiveSession, $this> */
    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }

    /** @return BelongsTo<Npc, $this> */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }
}
