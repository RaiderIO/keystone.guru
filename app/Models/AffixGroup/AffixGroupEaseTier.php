<?php

namespace App\Models\AffixGroup;

use App\Models\CacheModel;
use App\Models\Dungeon;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                    $id
 * @property int                    $affix_group_ease_tier_pull_id
 * @property int                    $affix_group_id
 * @property int                    $dungeon_id
 * @property string                 $tier
 * @property AffixGroupEaseTierPull $affixGroupEaseTierPull
 * @property AffixGroup             $affixGroup
 * @property Dungeon                $dungeon
 *
 * @mixin Eloquent
 */
class AffixGroupEaseTier extends CacheModel
{
    public $fillable = [
        'affix_group_ease_tier_pull_id',
        'affix_group_id',
        'dungeon_id',
        'tier',
    ];

    public $timestamps = false;

    public function affixGroupEaseTierPull(): BelongsTo
    {
        return $this->belongsTo(AffixGroupEaseTierPull::class);
    }

    public function affixGroup(): BelongsTo
    {
        return $this->belongsTo(AffixGroup::class);
    }

    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }
}
