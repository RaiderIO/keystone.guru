<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\belongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property $id int The ID of this Affix.
 * @property $season_id int
 * @property $seasonal_index int
 *
 * @property Collection|AffixGroupEaseTier[] $easetiers
 * @property Collection|Affix[] $affixes
 * @property Season $season
 *
 * @mixin Eloquent
 */
class AffixGroup extends CacheModel
{
    public $timestamps = false;
    public $with = ['affixes'];
    public $hidden = ['pivot'];
    public $fillable = ['season_id', 'seasonal_index'];
    protected $appends = ['text'];

    /**
     * @return BelongsTo
     */
    public function season()
    {
        return $this->belongsTo('App\Models\Season');
    }

    /**
     * @return BelongsToMany
     */
    public function affixes()
    {
        // I don't know why this suddenly needs an order by. After adding indexes to the database somehow the order of this was done by affix_id
        // rather than the normal id. This caused affixes to be misplaced in the Affixes page. But not elsewhere, so it's double strange.
        // No clue, this works so I'll keep it this way for the time being.
        return $this->belongsToMany('App\Models\Affix', 'affix_group_couplings')->orderBy('affix_group_couplings.id', 'asc');
    }

    /**
     * @return HasMany
     */
    public function easetiers()
    {
        return $this->hasMany('App\Models\AffixGroupEaseTier');
    }

    /**
     * @return string The text representation of this affix group.
     */
    public function getTextAttribute()
    {
        $result = [];
        foreach ($this->affixes as $affix) {
            /** @var $affix Affix */
            $result[] = $affix->name;
        }
        $result = implode(', ', $result);

        if ($this->seasonal_index !== null) {
            $result .= sprintf(' (%s)', $this->getSeasonalIndexAsLetter());
        }

        return $result;
    }

    /**
     * @return bool Checks if this group contains the Teeming affix.
     */
    public function isTeeming(): bool
    {
        return $this->hasAffix('Teeming');
    }

    /**
     * @return bool Checks if this group contains the Tyrannical affix.
     */
    public function isTyrannical(): bool
    {
        return $this->hasAffix('Tyrannical');
    }

    /**
     * @return bool Checks if this group contains the Fortified affix.
     */
    public function isFortified(): bool
    {
        return $this->hasAffix('Fortified');
    }

    /**
     * @return string|null
     */
    public function getSeasonalIndexAsLetter()
    {
        $result = null;

        if ($this->seasonal_index !== null) {
            $seasonalIndices = ['A', 'B', 'C', 'D', 'E'];
            $result = $seasonalIndices[$this->seasonal_index];
        }

        return $result;
    }

    /**
     * @param string $name
     * @return bool
     */
    private function hasAffix(string $name): bool
    {
        $result = false;

        foreach ($this->affixes as $affix) {
            /** @var $affix Affix */
            if ($result = ($affix->name === $name)) {
                break;
            }
        }

        return $result;
    }
}
