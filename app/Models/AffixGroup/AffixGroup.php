<?php

namespace App\Models\AffixGroup;

use App;
use App\Models\Season;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Season $season
 *
 * @mixin Eloquent
 */
class AffixGroup extends AffixGroupBase
{
    public $fillable = ['season_id', 'seasonal_index'];

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
     * @return HasMany
     */
    public function easetiers(): HasMany
    {
        return $this->hasMany('App\Models\AffixGroup\AffixGroupEaseTier');
    }
}
