<?php

namespace App\Models;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $current_affixes
 * @property string $source_url
 *
 * @property Carbon $last_updated_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Collection|AffixGroupEaseTier[] $affixgroupeasetiers
 *
 * @mixin Eloquent
 **/
class SubcreationEaseTierPull extends CacheModel
{
    protected $fillable = [
        'current_affixes',
        'source_url',
        'last_updated_at'
    ];

    /**
     * @return HasMany
     */
    public function affixgroupeasetiers()
    {
        return $this->hasMany('App\Models\AffixGroupEaseTier');
    }
}
