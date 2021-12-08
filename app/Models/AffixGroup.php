<?php

namespace App\Models;

use App;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\belongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property $id int The ID of this Affix.
 * @property $season_id int
 * @property $seasonal_index int
 * @property $seasonal_index_in_season int Only set in rare case - not a database column! See KeystoneGuruServiceProvider.php
 * @property $text string To string of the affix group
 *
 * @property Collection|AffixGroupEaseTier[] $easetiers
 * @property Collection|Affix[] $affixes
 * @property Season $season
 *
 * @mixin Eloquent
 */
class AffixGroup extends CacheModel
{
    const EVENT_LEGION_TIMEWALKING = 'legion_tw';

    public $timestamps = false;
    public $with = ['affixes'];
    public $hidden = ['pivot'];
    public $fillable = ['season_id', 'seasonal_index'];
    protected $appends = ['text'];

    /**
     * @return BelongsTo
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo('App\Models\Season');
    }

    /**
     * @return BelongsToMany
     */
    public function affixes(): BelongsToMany
    {
        // I don't know why this suddenly needs an order by. After adding indexes to the database somehow the order of this was done by affix_id
        // rather than the normal id. This caused affixes to be misplaced in the Affixes page. But not elsewhere, so it's double strange.
        // No clue, this works so I'll keep it this way for the time being.
        return $this->belongsToMany('App\Models\Affix', 'affix_group_couplings')->orderBy('affix_group_couplings.id', 'asc');
    }

    /**
     * @return HasMany
     */
    public function easetiers(): HasMany
    {
        return $this->hasMany('App\Models\AffixGroupEaseTier');
    }

    /**
     * @return string The text representation of this affix group.
     */
    public function getTextAttribute(): string
    {
        $result = [];
        foreach ($this->affixes as $affix) {
            /** @var $affix Affix */
            $result[] = __($affix->name);
        }
        $result = implode(', ', $result);

        if ($this->seasonal_index !== null) {
            $result = sprintf(__('affixes.seasonal_index_preset'), $result, $this->seasonal_index + 1);
        }

        return $result;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasAffix(string $key): bool
    {
        $result = false;

        foreach ($this->affixes as $affix) {
            /** @var $affix Affix */
            if ($result = ($affix->key === $key)) {
                break;
            }
        }

        return $result;
    }
}
