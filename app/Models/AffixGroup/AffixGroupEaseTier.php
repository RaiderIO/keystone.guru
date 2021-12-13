<?php

namespace App\Models\AffixGroup;

use App\Models\CacheModel;
use App\Models\Dungeon;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property $id int The ID of this Affix.
 * @property $subcreation_ease_tier_pull_id int
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
    public $fillable = ['subcreation_ease_tier_pull_id', 'affix_group_id', 'dungeon_id', 'tier'];

    /**
     * @return BelongsTo
     */
    public function subcreationeasetierpull()
    {
        return $this->belongsTo('App\Models\SubcreationEaseTierPull');
    }

    /**
     * @return BelongsTo
     */
    public function affixgroup()
    {
        return $this->belongsTo('App\Models\AffixGroup\AffixGroup');
    }

    /**
     * @return BelongsTo
     */
    public function dungeon()
    {
        return $this->belongsTo('App\Models\Dungeon');
    }
}
