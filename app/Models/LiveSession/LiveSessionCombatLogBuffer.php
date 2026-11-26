<?php

namespace App\Models\LiveSession;

use Database\Factories\LiveSession\LiveSessionCombatLogBufferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The reduced checkpoint of a live session's combat log: the gzip-compressed lines that must be replayed in
 * front of the next pending batches to rebuild the in-flight combat state.
 *
 * @property int         $id
 * @property int         $live_session_id
 * @property string|null $buffer
 *
 * @property LiveSession $liveSession
 */
class LiveSessionCombatLogBuffer extends Model
{
    /** @use HasFactory<LiveSessionCombatLogBufferFactory> */
    use HasFactory;

    protected $fillable = [
        'live_session_id',
        'buffer',
    ];

    protected $hidden = [
        'buffer',
    ];

    protected static function newFactory(): LiveSessionCombatLogBufferFactory
    {
        return LiveSessionCombatLogBufferFactory::new();
    }

    /** @return BelongsTo<LiveSession, $this> */
    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }
}
