<?php

namespace App\Models\LiveSession;

use App\Models\Npc\Npc;
use Database\Factories\Enemies\LiveSessionInCombatEnemyFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $live_session_id
 * @property int $npc_id
 * @property int $mdt_id
 *
 * @property LiveSession $liveSession
 * @property Npc         $npc
 *
 * @mixin Eloquent
 */
class LiveSessionInCombatEnemy extends Model
{
    /** @use HasFactory<LiveSessionInCombatEnemyFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'live_session_id',
        'npc_id',
        'mdt_id',
    ];

    protected static function newFactory(): LiveSessionInCombatEnemyFactory
    {
        return LiveSessionInCombatEnemyFactory::new();
    }

    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }
}
