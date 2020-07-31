<?php

namespace App\Models;

use App\Models\Traits\HasLinkedAwakenedObelisk;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $floor_id
 * @property int $dungeon_route_id
 * @property int $team_id
 * @property int $map_icon_type_id
 * @property float $lat
 * @property float $lng
 * @property string $comment
 * @property boolean $permanent_tooltip
 * @property int $seasonal_index
 *
 * @property \App\Models\Floor $floor
 * @property \App\Models\DungeonRoute $dungeonroute
 * @property \App\Models\MapIconType $mapicontype
 *
 * @mixin \Eloquent
 */
class MapIcon extends Model
{
    use HasLinkedAwakenedObelisk;

    protected $visible = ['id', 'floor_id', 'team_id', 'map_icon_type_id', 'linked_awakened_obelisk_id', 'lat', 'lng', 'comment', 'permanent_tooltip', 'seasonal_index'];
    protected $fillable = ['floor_id', 'dungeon_route_id', 'team_id', 'map_icon_type_id', 'lat', 'lng', 'comment', 'permanent_tooltip'];
    protected $appends = ['linked_awakened_obelisk_id'];

    protected $with = ['mapicontype'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function dungeonroute()
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function mapicontype()
    {
        // Need the foreign key for some reason
        return $this->belongsTo('App\Models\MapIconType', 'map_icon_type_id');
    }

    /**
     * @return bool
     */
    public function isAwakenedObelisk()
    {
        return $this->map_icon_type_id >= 17 && $this->map_icon_type_id <= 20;
    }
}
