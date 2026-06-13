<?php

namespace App\Models\LiveSession;

use App\Models\Floor\Floor;
use App\Models\Interfaces\HasLatLngInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\HasLatLng;
use Database\Factories\LiveSession\LiveSessionPlayerPositionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int    $id
 * @property int    $live_session_id
 * @property int    $floor_id
 * @property string $player_guid
 * @property string $character_name
 * @property float  $lat
 * @property float  $lng
 * @property Carbon $updated_at
 *
 * @property LiveSession    $liveSession
 * @property Floor          $floor
 * @property MappingVersion $mappingVersion
 *
 * @mixin Eloquent
 */
class LiveSessionPlayerPosition extends Model implements HasLatLngInterface
{
    /** @use HasFactory<LiveSessionPlayerPositionFactory> */
    use HasFactory;
    use HasLatLng;

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

    protected $hidden = [
        'live_session_id',
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

    /**
     * Resolves the mapping version via the live session's dungeon route.
     * Used by HasLatLng::getCoordinatesData() to perform facade coordinate conversion.
     */
    public function getMappingVersionAttribute(): ?MappingVersion
    {
        return $this->liveSession->dungeonRoute?->mappingVersion;
    }

    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }
}
