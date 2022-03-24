<?php

namespace App\Models\AffixGroup;

use App;
use App\Models\Expansion;
use App\Models\Season;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $season_id
 * @property int $expansion_id
 * @property int|null $seasonal_index
 * @property bool $confirmed
 *
 * @property Season $season
 * @property Expansion $expansion
 *
 * @mixin Eloquent
 */
class AffixGroup extends AffixGroupBase
{
    public $fillable = ['season_id', 'seasonal_index', 'confirmed'];


    protected function getAffixGroupCouplingsTableName(): string
    {
        return 'affix_group_couplings';
    }

    /**
     * @return BelongsTo
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo('App\Models\Season');
    }

    /**
     * @return BelongsTo
     */
    public function expansion(): BelongsTo
    {
        return $this->belongsTo('App\Models\Expansion');
    }

    /**
     * @return HasMany
     */
    public function easetiers(): HasMany
    {
        return $this->hasMany('App\Models\AffixGroup\AffixGroupEaseTier');
    }
}
