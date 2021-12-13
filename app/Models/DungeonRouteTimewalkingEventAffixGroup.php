<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property $id int
 * @property $dungeon_route_id int
 * @property $timewalking_event_affix_group_id int
 *
 * @mixin Eloquent
 */
class DungeonRouteTimewalkingEventAffixGroup extends Model
{
    public $hidden = ['id'];
    public $fillable = [
        'dungeon_route_id',
        'timewalking_event_affix_group_id',
    ];

    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function dungeonroute(): BelongsTo
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }

    /**
     * @return BelongsTo
     */
    public function timewalkingeventaffixgroup(): BelongsTo
    {
        return $this->belongsTo('App\Models\Timewalking\TimewalkingAffixGroup');
    }
}
