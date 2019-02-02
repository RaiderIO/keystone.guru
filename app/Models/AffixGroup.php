<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int The ID of this Affix.
 * @property $enabled boolean
 * @property $affix \Illuminate\Database\Eloquent\Collection
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder inactive()
 */
class AffixGroup extends Model
{
    public $timestamps = false;
    public $with = ['affixes'];
    public $hidden = ['pivot'];
    protected $appends = ['text'];

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
     * Scope a query to only include active dungeons.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * Scope a query to only include inactive dungeons.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('active', 0);
    }
}
