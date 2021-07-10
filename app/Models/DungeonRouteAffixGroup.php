<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property $id int
 * @property $dungeon_route_id int
 * @property $affix_group_id int
 *
 * @mixin Eloquent
 */
class DungeonRouteAffixGroup extends Model
{
    public $hidden = ['id'];
    public $fillable = [
        'dungeon_route_id',
        'affix_group_id',
    ];

    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function dungeonroute()
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }

    /**
     * @return BelongsTo
     */
    public function affixgroup()
    {
        return $this->belongsTo('App\Models\AffixGroup');
    }
}
