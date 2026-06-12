<?php

namespace App\Models\LiveSession;

use Database\Factories\LiveSessionPlayerPositionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int    $id
 * @property int    $live_session_id
 * @property string $player_guid
 * @property string $character_name
 * @property float  $lat
 * @property float  $lng
 * @property int    $floor_id
 * @property Carbon $updated_at
 *
 * @property LiveSession $liveSession
 *
 * @mixin Eloquent
 */
class LiveSessionPlayerPosition extends Model
{
    /** @use HasFactory<LiveSessionPlayerPositionFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'live_session_id',
        'player_guid',
        'character_name',
        'lat',
        'lng',
        'floor_id',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): LiveSessionPlayerPositionFactory
    {
        return LiveSessionPlayerPositionFactory::new();
    }

    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }
}
