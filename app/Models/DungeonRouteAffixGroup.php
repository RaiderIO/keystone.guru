<?php

namespace App\Models;

use App\Models\AffixGroup\AffixGroup;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $affix_group_id
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
    public function dungeonroute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /**
     * @return BelongsTo
     */
    public function affixgroup(): BelongsTo
    {
        return $this->belongsTo(AffixGroup::class);
    }
}
