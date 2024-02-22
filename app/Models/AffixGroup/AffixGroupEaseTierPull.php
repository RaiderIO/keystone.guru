<?php

namespace App\Models\AffixGroup;

use App\Models\CacheModel;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int                             $id
 * @property int                             $affix_group_id
 * @property string                          $tiers_hash
 *
 * @property Carbon                          $last_updated_at
 * @property Carbon                          $created_at
 * @property Carbon                          $updated_at
 *
 * @property AffixGroup                      $affixGroup
 * @property Collection|AffixGroupEaseTier[] $affixGroupEaseTiers
 *
 * @mixin Eloquent
 **/
class AffixGroupEaseTierPull extends CacheModel
{
    protected $with = ['affixGroup', 'affixGroupEaseTiers'];

    protected $fillable = [
        'affix_group_id',
        'tiers_hash',
        'last_updated_at',
    ];

    /**
     * @return BelongsTo
     */
    public function affixGroup(): BelongsTo
    {
        return $this->belongsTo(AffixGroup::class);
    }

    /**
     * @return HasMany
     */
    public function affixGroupEaseTiers(): HasMany
    {
        return $this->hasMany(AffixGroupEaseTier::class);
    }

    /**
     * @return string
     */
    public function getAffixGroupEaseTiersHash(): string
    {
        return md5(
            $this->affixGroupEaseTiers->sort(function (AffixGroupEaseTier $affixGroupEaseTier) {
                return __($affixGroupEaseTier->dungeon->name, [], 'en-US');
            })->map(function (AffixGroupEaseTier $affixGroupEaseTier) {
                return sprintf(
                    '%s|%s|%s',
                    __($affixGroupEaseTier->dungeon->name, [], 'en-US'),
                    $affixGroupEaseTier->affixGroup->text,
                    $affixGroupEaseTier->tier
                );
            })->join('|')
        );
    }
}
