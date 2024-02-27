<?php

namespace App\Models\DungeonRoute;

use App\Models\AffixGroup\AffixGroup;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int          $id
 * @property int          $dungeon_route_id
 * @property int          $affix_group_id
 * @property DungeonRoute $dungeonRoute
 * @property AffixGroup   $affixGroup
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

    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    public function affixGroup(): BelongsTo
    {
        return $this->belongsTo(AffixGroup::class);
    }
}
