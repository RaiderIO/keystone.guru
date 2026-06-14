<?php

namespace App\Models\LiveSession;

use Database\Factories\LiveSession\LiveSessionCombatLogBufferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $live_session_id
 * @property string|null $buffer
 * @property int|null    $last_sequence
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
        'last_sequence',
    ];

    protected $hidden = [
        'buffer',
    ];

    protected static function newFactory(): LiveSessionCombatLogBufferFactory
    {
        return LiveSessionCombatLogBufferFactory::new();
    }

    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }
}
