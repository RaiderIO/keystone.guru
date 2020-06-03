<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int The ID of this Affix.
 * @property $season_id int
 * @property $seasonal_index int
 *
 * @property \Illuminate\Database\Eloquent\Collection $affixes
 * @property Season $season
 *
 * @mixin \Eloquent
 */
class AffixGroup extends Model
{
    public $timestamps = false;
    public $with = ['affixes'];
    public $hidden = ['pivot'];
    protected $appends = ['text'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function season()
    {
        return $this->belongsTo('App\Models\Season');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function affixes()
    {
        // I don't know why this suddenly needs an order by. After adding indexes to the database somehow the order of this was done by affix_id
        // rather than the normal id. This caused affixes to be misplaced in the Affixes page. But not elsewhere, so it's double strange.
        // No clue, this works so I'll keep it this way for the time being.
        return $this->belongsToMany('App\Models\Affix', 'affix_group_couplings')->orderBy('affix_group_couplings.id', 'asc');
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
        return implode(', ', $result);
    }

    /**
     * @return bool Checks if this group contains the Teeming affix.
     */
    public function isTeeming()
    {
        $result = false;

        foreach ($this->affixes as $affix) {
            /** @var $affix Affix */
            // A bit hacky I guess? But Teeming is such a special case that I'm justifying this magic string
            if ($result = ($affix->name === 'Teeming')) {
                break;
            }
        }

        return $result;
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
}
