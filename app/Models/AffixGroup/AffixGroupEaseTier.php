<?php

namespace App\Models\AffixGroup;

use App\Models\CacheModel;
use App\Models\Dungeon;
use App\Models\SubcreationEaseTierPull;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id The ID of this Affix.
 * @property int $subcreation_ease_tier_pull_id
 * @property int $affix_group_id
 * @property int $dungeon_id
 * @property string $tier
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
    public function subcreationeasetierpull(): BelongsTo
    {
        return $this->belongsTo(SubcreationEaseTierPull::class);
    }

    /**
     * @return BelongsTo
     */
    public function affixgroup(): BelongsTo
    {
        return $this->belongsTo(AffixGroup::class);
    }

    /**
     * @return BelongsTo
     */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }
}
