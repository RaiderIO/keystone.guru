<?php

namespace App\Models\AffixGroup;

use App;
use App\Models\Expansion;
use App\Models\Season;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $season_id
 * @property int $expansion_id
 * @property int|null $seasonal_index
 * @property bool $confirmed
 *
 * @property Season $season
 * @property Expansion $expansion
 * @property Collection|AffixGroupEaseTier[] $easetiers
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
        return $this->belongsTo(Season::class);
    }

    /**
     * @return BelongsTo
     */
    public function expansion(): BelongsTo
    {
        return $this->belongsTo(Expansion::class);
    }

    /**
     * @return HasMany
     */
    public function easetiers(): HasMany
    {
        return $this->hasMany(AffixGroupEaseTier::class);
    }
}
