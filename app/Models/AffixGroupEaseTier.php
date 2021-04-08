<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property $id int The ID of this Affix.
 * @property $affix_group_id int
 * @property $dungeon_id int
 * @property $tier string
 *
 * @property AffixGroup $affixgroup
 * @property Dungeon $dungeon
 *
 * @mixin Eloquent
 */
class AffixGroupEaseTier extends CacheModel
{
    public $with = ['affixgroup', 'dungeon'];
    public $fillable = ['affix_group_id', 'dungeon_id', 'tier'];

    /**
     * @return BelongsTo
     */
    public function affixgroup()
    {
        return $this->belongsTo('App\Models\AffixGroup');
    }

    /**
     * @return BelongsTo
     */
    public function dungeon()
    {
        return $this->belongsTo('App\Models\Dungeon');
    }
}
