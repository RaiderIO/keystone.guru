<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property $id int
 * @property $kill_zone_id int
 * @property $enemy_id int
 *
 * @property KillZone $killzone
 * @property Enemy $enemy
 *
 * @mixin Eloquent
 */
class KillZoneEnemy extends Model
{
    public $hidden = ['id', 'kill_zone_id'];

    public $timestamps = false;

    protected $fillable = [
        'kill_zone_id',
        'enemy_id',
    ];

    /**
     * @return BelongsTo
     */
    public function killzone(): BelongsTo
    {
        return $this->belongsTo('App\Models\KillZone');
    }

    /**
     * @return BelongsTo
     */
    public function enemy(): BelongsTo
    {
        return $this->belongsTo('App\Models\Enemy');
    }
}
