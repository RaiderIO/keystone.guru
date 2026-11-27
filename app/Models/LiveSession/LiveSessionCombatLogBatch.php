<?php

namespace App\Models\LiveSession;

use Database\Factories\LiveSession\LiveSessionCombatLogBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One batch of raw combat-log lines as posted by the live session client. A consumed batch keeps its row (without
 * its lines) so a retried sequence number is still recognised as a duplicate.
 *
 * @property int         $id
 * @property int         $live_session_id
 * @property int|null    $sequence
 * @property string|null $lines           gzip-compressed, newline-separated raw combat-log lines; null once consumed
 * @property Carbon|null $consumed_at
 * @property Carbon|null $created_at
 *
 * @property LiveSession $liveSession
 */
class LiveSessionCombatLogBatch extends Model
{
    /** @use HasFactory<LiveSessionCombatLogBatchFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'live_session_id',
        'sequence',
        'lines',
        'consumed_at',
    ];

    protected $hidden = [
        'lines',
    ];

    protected function casts(): array
    {
        return [
            'sequence'    => 'integer',
            'consumed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): LiveSessionCombatLogBatchFactory
    {
        return LiveSessionCombatLogBatchFactory::new();
    }

    /** @return BelongsTo<LiveSession, $this> */
    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }
}
