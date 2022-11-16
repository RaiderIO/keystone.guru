<?php

namespace App\Models\Enemies;

use App\Models\Enemy;
use App\Models\KillZone;
use App\Models\LiveSession;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $live_session_id
 * @property int $kill_zone_id
 * @property int $enemy_id
 *
 * @property LiveSession $livesession
 * @property KillZone $killzone
 * @property Enemy $enemy
 *
 * @mixin Eloquent
 */
class OverpulledEnemy extends Model
{
    protected $fillable = [
        'live_session_id',
        'kill_zone_id',
        'enemy_id',
    ];

    protected $visible = ['enemy_id', 'kill_zone_id'];

    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function livesession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }

    /**
     * @return BelongsTo
     */
    public function killzone(): BelongsTo
    {
        return $this->belongsTo(KillZone::class);
    }

    /**
     * @return BelongsTo
     */
    public function enemy(): BelongsTo
    {
        return $this->belongsTo(Enemy::class);
    }
}
