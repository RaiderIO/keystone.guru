<?php

namespace App\Models\Enemies;

use App\Models\Enemy;
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
    function livesession()
    {
        return $this->belongsTo('App\Models\LiveSession');
    }

    /**
     * @return BelongsTo
     */
    function killzone()
    {
        return $this->belongsTo('App\Models\KillZone');
    }

    /**
     * @return BelongsTo
     */
    function enemy()
    {
        return $this->belongsTo('App\Models\Enemy');
    }
}
